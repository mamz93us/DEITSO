<?php

declare(strict_types=1);

namespace App\Services\DnsProvider;

use App\Models\DnsProviderAccount;
use InvalidArgumentException;
use RuntimeException;

/**
 * Resolves a concrete DnsProviderInterface from a DnsProviderAccount row.
 *
 * Add new providers by mapping the `provider` enum to its class here.
 */
class DnsProviderManager
{
    /**
     * @var array<string, class-string<DnsProviderInterface>>
     */
    protected array $map = [
        DnsProviderAccount::PROVIDER_GODADDY => GoDaddyProvider::class,
        // PROVIDER_CLOUDFLARE => CloudflareProvider::class, // future
        // PROVIDER_ROUTE53 => Route53Provider::class,       // future
    ];

    public function forAccount(DnsProviderAccount $account): DnsProviderInterface
    {
        $class = $this->map[$account->provider] ?? null;
        if ($class === null) {
            throw new InvalidArgumentException("Unsupported DNS provider: {$account->provider}");
        }

        return new $class($account);
    }

    public function default(): DnsProviderInterface
    {
        $account = DnsProviderAccount::query()
            ->where('is_default', true)
            ->where('status', 'active')
            ->orderBy('created_at')
            ->first();

        if (! $account) {
            throw new RuntimeException('No default DNS provider account configured.');
        }

        return $this->forAccount($account);
    }
}
