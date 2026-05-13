<?php

declare(strict_types=1);

namespace App\Services\DnsProvider;

use App\Models\DnsProviderAccount;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;

/**
 * GoDaddy DNS provider implementation.
 *
 * Auth: `Authorization: sso-key {key}:{secret}` header (NOT HMAC).
 * Sandbox: `https://api.ote-godaddy.com` ; production: `https://api.godaddy.com`.
 *
 * NOTE: production access requires the GoDaddy account to manage 10+ domains
 * or be on a paid tier. Use the OTE sandbox for dev and staging.
 */
class GoDaddyProvider extends AbstractDnsProvider
{
    protected ?ClientInterface $http = null;

    public function setHttpClient(ClientInterface $client): self
    {
        $this->http = $client;

        return $this;
    }

    public function createSubdomain(string $name, string $target): array
    {
        // GoDaddy's PATCH adds records; PUT replaces all records of a (type, name) pair.
        // We use PATCH to be additive (idempotent across re-runs).
        $payload = [[
            'type' => 'CNAME',
            'name' => $name,
            'data' => rtrim($target, '.').'.',
            'ttl' => 600,
        ]];

        $response = $this->client()->request(
            'PATCH',
            "/v1/domains/{$this->account->base_domain}/records",
            [
                'headers' => $this->headers(),
                'json' => $payload,
            ]
        );

        return [
            'status_code' => $response->getStatusCode(),
            'body' => (string) $response->getBody(),
            'payload_sent' => $payload,
        ];
    }

    public function recordExists(string $name): bool
    {
        try {
            $response = $this->client()->request(
                'GET',
                "/v1/domains/{$this->account->base_domain}/records/CNAME/{$name}",
                ['headers' => $this->headers()],
            );

            $records = json_decode((string) $response->getBody(), true);

            return is_array($records) && $records !== [];
        } catch (ClientException $e) {
            if ($e->getResponse()?->getStatusCode() === 404) {
                return false;
            }

            Log::warning('godaddy.recordExists failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function removeSubdomain(string $name): void
    {
        try {
            $this->client()->request(
                'DELETE',
                "/v1/domains/{$this->account->base_domain}/records/CNAME/{$name}",
                ['headers' => $this->headers()],
            );
        } catch (ClientException $e) {
            // 404 = already gone; everything else is logged but does not throw.
            if ($e->getResponse()?->getStatusCode() !== 404) {
                Log::warning('godaddy.removeSubdomain failed', ['error' => $e->getMessage()]);
            }
        }
    }

    protected function client(): ClientInterface
    {
        if ($this->http !== null) {
            return $this->http;
        }

        $base = $this->account->environment === DnsProviderAccount::ENV_OTE
            ? 'https://api.ote-godaddy.com'
            : 'https://api.godaddy.com';

        return new Client([
            'base_uri' => $base,
            'timeout' => 10,
            'http_errors' => true,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function headers(): array
    {
        $creds = $this->account->credentials_encrypted ?? [];
        $key = $creds['api_key'] ?? '';
        $secret = $creds['api_secret'] ?? '';

        return [
            'Authorization' => "sso-key {$key}:{$secret}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
