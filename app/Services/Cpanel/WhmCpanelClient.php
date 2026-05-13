<?php

declare(strict_types=1);

namespace App\Services\Cpanel;

use App\Models\MailServer;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Hand-rolled WHM/cPanel UAPI client. Talks to the WHM API on port 2087 with
 * an API token in the Authorization header:
 *   Authorization: whm $username:$token
 *
 * Methods return an array { status, response, error? } so callers can write a
 * uniform audit row regardless of success/failure.
 *
 * Network operations are kept short (10s timeout) — long-running batch ops
 * should be wrapped in queued jobs by the calling Action.
 */
class WhmCpanelClient implements CpanelClientInterface
{
    protected ?ClientInterface $http = null;

    public function setHttpClient(ClientInterface $client): self
    {
        $this->http = $client;

        return $this;
    }

    public function ping(MailServer $server): array
    {
        return $this->call($server, 'GET', 'json-api/listaccts', ['api.version' => 1]);
    }

    public function createEmailAccount(MailServer $server, string $domain, string $localPart, string $password, ?int $quotaMb = null): array
    {
        return $this->call($server, 'POST', 'json-api/cpanel', [
            'cpanel_jsonapi_user' => $domain, // cpanel user — usually the domain owner
            'cpanel_jsonapi_apiversion' => 2,
            'cpanel_jsonapi_module' => 'Email',
            'cpanel_jsonapi_func' => 'addpop',
            'domain' => $domain,
            'email' => $localPart,
            'password' => $password,
            'quota' => $quotaMb ?: 0,
        ]);
    }

    public function resetPassword(MailServer $server, string $email, string $newPassword): array
    {
        [$local, $domain] = $this->split($email);

        return $this->call($server, 'POST', 'json-api/cpanel', [
            'cpanel_jsonapi_user' => $domain,
            'cpanel_jsonapi_apiversion' => 2,
            'cpanel_jsonapi_module' => 'Email',
            'cpanel_jsonapi_func' => 'passwdpop',
            'email' => $local,
            'domain' => $domain,
            'password' => $newPassword,
        ]);
    }

    public function changeQuota(MailServer $server, string $email, int $quotaMb): array
    {
        [$local, $domain] = $this->split($email);

        return $this->call($server, 'POST', 'json-api/cpanel', [
            'cpanel_jsonapi_user' => $domain,
            'cpanel_jsonapi_apiversion' => 2,
            'cpanel_jsonapi_module' => 'Email',
            'cpanel_jsonapi_func' => 'editquota',
            'email' => $local,
            'domain' => $domain,
            'quota' => $quotaMb,
        ]);
    }

    public function suspend(MailServer $server, string $email): array
    {
        [$local, $domain] = $this->split($email);

        return $this->call($server, 'POST', 'json-api/cpanel', [
            'cpanel_jsonapi_user' => $domain,
            'cpanel_jsonapi_apiversion' => 2,
            'cpanel_jsonapi_module' => 'Email',
            'cpanel_jsonapi_func' => 'suspend_login',
            'email' => $local,
            'domain' => $domain,
        ]);
    }

    public function unsuspend(MailServer $server, string $email): array
    {
        [$local, $domain] = $this->split($email);

        return $this->call($server, 'POST', 'json-api/cpanel', [
            'cpanel_jsonapi_user' => $domain,
            'cpanel_jsonapi_apiversion' => 2,
            'cpanel_jsonapi_module' => 'Email',
            'cpanel_jsonapi_func' => 'unsuspend_login',
            'email' => $local,
            'domain' => $domain,
        ]);
    }

    public function deleteAccount(MailServer $server, string $email): array
    {
        [$local, $domain] = $this->split($email);

        return $this->call($server, 'POST', 'json-api/cpanel', [
            'cpanel_jsonapi_user' => $domain,
            'cpanel_jsonapi_apiversion' => 2,
            'cpanel_jsonapi_module' => 'Email',
            'cpanel_jsonapi_func' => 'delpop',
            'email' => $local,
            'domain' => $domain,
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array{status: string, response: array|null, error?: string}
     */
    protected function call(MailServer $server, string $method, string $endpoint, array $params): array
    {
        try {
            $response = $this->client($server)->request($method, $endpoint, [
                'headers' => [
                    'Authorization' => sprintf('whm %s:%s', $server->username, $server->api_token_encrypted),
                    'Accept' => 'application/json',
                ],
                'query' => $method === 'GET' ? $params : [],
                'form_params' => $method === 'POST' ? $params : [],
            ]);

            $body = json_decode((string) $response->getBody(), true) ?? [];

            return [
                'status' => 'success',
                'response' => $body,
            ];
        } catch (GuzzleException $e) {
            Log::warning('cpanel.call failed', [
                'server' => $server->hostname,
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function client(MailServer $server): ClientInterface
    {
        if ($this->http !== null) {
            return $this->http;
        }

        return new Client([
            'base_uri' => sprintf('https://%s:%d/', $server->hostname, $server->port ?: 2087),
            'timeout' => 10,
            'http_errors' => true,
            'verify' => true,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function split(string $email): array
    {
        $parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException("Invalid email address: {$email}");
        }

        return $parts;
    }
}
