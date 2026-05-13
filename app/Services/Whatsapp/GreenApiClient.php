<?php

declare(strict_types=1);

namespace App\Services\Whatsapp;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Minimal Green-API WhatsApp client. Per-org credentials (id_instance + token)
 * are stored on the Organization.settings JSON blob. This client is
 * intentionally thin — actual notification routing happens in
 * App\Channels\WhatsappChannel which adapts Laravel notifications.
 *
 * Usage:
 *   $client = (new GreenApiClient($idInstance, $token))->sendText($e164, $msg);
 *
 * `sendText` is idempotent on Green-API's side — re-sending with the same
 * content is a no-op there but our caller should avoid sending twice.
 */
class GreenApiClient
{
    protected ClientInterface $http;

    public function __construct(
        protected string $idInstance,
        protected string $apiToken,
        ?ClientInterface $http = null,
    ) {
        $this->http = $http ?? new Client([
            'base_uri' => "https://api.green-api.com/waInstance{$idInstance}/",
            'timeout' => 10,
            'http_errors' => true,
        ]);
    }

    /**
     * Send a plain-text WhatsApp message.
     *
     * @return array{status: string, message_id?: string, error?: string}
     */
    public function sendText(string $phoneE164, string $message): array
    {
        $chatId = $this->phoneToChatId($phoneE164);

        try {
            $response = $this->http->request('POST', "sendMessage/{$this->apiToken}", [
                'json' => [
                    'chatId' => $chatId,
                    'message' => $message,
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true) ?? [];

            return [
                'status' => 'success',
                'message_id' => $body['idMessage'] ?? null,
            ];
        } catch (GuzzleException $e) {
            Log::warning('greenapi.sendText failed', [
                'phone' => $phoneE164,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * "+201234567890" → "201234567890@c.us"
     */
    protected function phoneToChatId(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);
        if ($digits === '' || $digits === null) {
            throw new InvalidArgumentException("Invalid phone number: {$phone}");
        }

        return $digits.'@c.us';
    }
}
