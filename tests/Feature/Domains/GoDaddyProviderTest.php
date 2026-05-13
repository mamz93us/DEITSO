<?php

declare(strict_types=1);

use App\Models\DnsProviderAccount;
use App\Services\DnsProvider\GoDaddyProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

function makeGoDaddyClient(array $responses, array &$container): Client
{
    $mock = new MockHandler($responses);
    $stack = HandlerStack::create($mock);
    $stack->push(Middleware::history($container));

    return new Client(['handler' => $stack, 'http_errors' => true]);
}

it('sends the correct sso-key auth header and payload when creating a subdomain', function () {
    $container = [];
    $client = makeGoDaddyClient([new Response(200, [], '[]')], $container);

    $account = DnsProviderAccount::create([
        'provider' => DnsProviderAccount::PROVIDER_GODADDY,
        'name' => 'Test',
        'base_domain' => 'example.test',
        'environment' => DnsProviderAccount::ENV_OTE,
        'is_default' => true,
        'status' => 'active',
        'credentials_encrypted' => ['api_key' => 'KEY123', 'api_secret' => 'SECRET456'],
    ]);

    $provider = (new GoDaddyProvider($account))->setHttpClient($client);
    $result = $provider->createSubdomain('acme', 'app.example.test');

    expect($result['status_code'])->toBe(200);

    /** @var RequestInterface $req */
    $req = $container[0]['request'];
    expect($req->getMethod())->toBe('PATCH')
        ->and((string) $req->getUri())->toEndWith('/v1/domains/example.test/records')
        ->and($req->getHeaderLine('Authorization'))->toBe('sso-key KEY123:SECRET456');

    $body = json_decode((string) $req->getBody(), true);
    expect($body)->toBeArray()
        ->and($body[0]['type'])->toBe('CNAME')
        ->and($body[0]['name'])->toBe('acme')
        ->and($body[0]['data'])->toBe('app.example.test.');
});

it('treats a 404 as record-does-not-exist without throwing', function () {
    $history = [];
    $client = makeGoDaddyClient([new Response(404, [], 'not found')], $history);

    $account = DnsProviderAccount::create([
        'provider' => DnsProviderAccount::PROVIDER_GODADDY,
        'name' => 'Test',
        'base_domain' => 'example.test',
        'environment' => DnsProviderAccount::ENV_OTE,
        'is_default' => true,
        'status' => 'active',
        'credentials_encrypted' => ['api_key' => 'k', 'api_secret' => 's'],
    ]);

    $provider = (new GoDaddyProvider($account))->setHttpClient($client);

    expect($provider->recordExists('missing'))->toBeFalse();
});
