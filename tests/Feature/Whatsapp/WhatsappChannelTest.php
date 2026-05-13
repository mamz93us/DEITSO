<?php

declare(strict_types=1);

use App\Channels\WhatsappChannel;
use App\Models\Organization;
use App\Services\Whatsapp\GreenApiClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Notifications\Notification;
use Psr\Http\Message\RequestInterface;

it('builds the Green API chatId from a phone number', function () {
    $client = new GreenApiClient('1234', 'token');

    $reflect = new ReflectionMethod(GreenApiClient::class, 'phoneToChatId');
    $reflect->setAccessible(true);

    expect($reflect->invoke($client, '+201234567890'))->toBe('201234567890@c.us')
        ->and($reflect->invoke($client, '20 12 3456-7890'))->toBe('201234567890@c.us');
});

it('sends a WhatsApp message via Green API and returns success', function () {
    $container = [];
    $stack = HandlerStack::create(new MockHandler([
        new Response(200, [], json_encode(['idMessage' => 'msg-123'])),
    ]));
    $stack->push(Middleware::history($container));
    $http = new Client(['handler' => $stack, 'http_errors' => true]);

    $client = new GreenApiClient('idi', 'tok', $http);
    $result = $client->sendText('+201234567890', 'Hello');

    expect($result)->toMatchArray([
        'status' => 'success',
        'message_id' => 'msg-123',
    ]);

    /** @var RequestInterface $req */
    $req = $container[0]['request'];
    $body = json_decode((string) $req->getBody(), true);
    expect($body)->toMatchArray([
        'chatId' => '201234567890@c.us',
        'message' => 'Hello',
    ]);
});

it('returns failed when Green API errors', function () {
    $stack = HandlerStack::create(new MockHandler([
        new RequestException(
            'oh no',
            new Request('POST', '/')
        ),
    ]));
    $http = new Client(['handler' => $stack, 'http_errors' => true]);

    $client = new GreenApiClient('idi', 'tok', $http);
    $result = $client->sendText('+201234567890', 'Hello');

    expect($result['status'])->toBe('failed')
        ->and($result['error'])->toContain('oh no');
});

it('WhatsappChannel skips silently when org has no Green API config', function () {
    // Should NOT throw — the channel must tolerate missing config.
    $channel = new WhatsappChannel;

    $notifiable = new class
    {
        public ?Organization $organization = null;
    };
    $notification = new class extends Notification
    {
        public function toWhatsapp(object $n): array
        {
            return ['phone' => '+201111111111', 'message' => 'test'];
        }
    };

    $channel->send($notifiable, $notification);

    // No exception. Test passes simply by not throwing.
    expect(true)->toBeTrue();
});
