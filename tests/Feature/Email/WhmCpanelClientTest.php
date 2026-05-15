<?php

declare(strict_types=1);

use App\Models\MailServer;
use App\Models\Organization;
use App\Services\Cpanel\WhmCpanelClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

function makeWhmClient(array $responses, array &$container): Client
{
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($container));

    return new Client(['handler' => $stack, 'http_errors' => true]);
}

function makeMailServer(): MailServer
{
    $org = Organization::create(['slug' => 'whmco', 'name' => ['en' => 'WHM Co'], 'status' => 'active']);
    app()->instance('current.organization', $org);

    return MailServer::create([
        'organization_id' => $org->id,
        'name' => 'Primary',
        'hostname' => 'mail.example.test',
        'username' => 'root',
        'api_token_encrypted' => 'tok-xyz',
        'port' => 2087,
        'status' => MailServer::STATUS_ACTIVE,
    ]);
}

it('sends the WHM Authorization header in the documented `whm user:token` format', function () {
    $container = [];
    $client = makeWhmClient([new Response(200, [], '{"data":{"acct":[]}}')], $container);

    $server = makeMailServer();
    $result = (new WhmCpanelClient)->setHttpClient($client)->ping($server);

    expect($result['status'])->toBe('success');

    /** @var RequestInterface $req */
    $req = $container[0]['request'];
    expect($req->getHeaderLine('Authorization'))->toBe('whm root:tok-xyz')
        ->and($req->getMethod())->toBe('GET')
        ->and((string) $req->getUri())->toContain('json-api/listaccts')
        ->and($req->getUri()->getQuery())->toBe('api.version=1');
});

it('POSTs createEmailAccount with form_params, never as JSON', function () {
    $container = [];
    $client = makeWhmClient([new Response(200, [], '{"cpanelresult":{"event":{"result":1}}}')], $container);

    $server = makeMailServer();
    $result = (new WhmCpanelClient)->setHttpClient($client)
        ->createEmailAccount($server, 'example.test', 'alice', 'pw-secret', 1024);

    expect($result['status'])->toBe('success');

    /** @var RequestInterface $req */
    $req = $container[0]['request'];
    expect($req->getMethod())->toBe('POST')
        ->and((string) $req->getUri())->toEndWith('json-api/cpanel')
        ->and($req->getHeaderLine('Content-Type'))->toContain('application/x-www-form-urlencoded');

    parse_str((string) $req->getBody(), $form);
    expect($form)->toMatchArray([
        'cpanel_jsonapi_user' => 'example.test',
        'cpanel_jsonapi_module' => 'Email',
        'cpanel_jsonapi_func' => 'addpop',
        'domain' => 'example.test',
        'email' => 'alice',
        'password' => 'pw-secret',
        'quota' => '1024',
    ]);
});

it('returns failed with a captured error message when WHM returns 401', function () {
    $container = [];
    $client = makeWhmClient([new Response(401, [], 'unauthorized')], $container);

    $server = makeMailServer();
    $result = (new WhmCpanelClient)->setHttpClient($client)
        ->createEmailAccount($server, 'example.test', 'bob', 'pw');

    expect($result['status'])->toBe('failed')
        ->and($result['response'])->toBeNull()
        ->and($result['error'])->toBeString()
        ->and($result['error'])->not->toBeEmpty();
});

it('splits the email address on @ for resetPassword and uses domain as cpanel user', function () {
    $container = [];
    $client = makeWhmClient([new Response(200, [], '{}')], $container);

    $server = makeMailServer();
    (new WhmCpanelClient)->setHttpClient($client)
        ->resetPassword($server, 'carol@example.test', 'new-pw');

    /** @var RequestInterface $req */
    $req = $container[0]['request'];
    parse_str((string) $req->getBody(), $form);
    expect($form)->toMatchArray([
        'cpanel_jsonapi_func' => 'passwdpop',
        'cpanel_jsonapi_user' => 'example.test',
        'email' => 'carol',
        'domain' => 'example.test',
        'password' => 'new-pw',
    ]);
});

it('throws InvalidArgumentException when an email lacks @ in split-required methods', function () {
    $server = makeMailServer();
    $client = new Client; // unused

    expect(fn () => (new WhmCpanelClient)->setHttpClient($client)->resetPassword($server, 'no-at-sign', 'pw'))
        ->toThrow(InvalidArgumentException::class, 'Invalid email address');
});
