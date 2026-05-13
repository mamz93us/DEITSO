<?php

declare(strict_types=1);

namespace App\Services\DnsProvider;

use App\Models\DnsProviderAccount;

abstract class AbstractDnsProvider implements DnsProviderInterface
{
    public function __construct(protected DnsProviderAccount $account) {}

    /**
     * Best-effort DNS resolution check. Returns true if $fqdn resolves to any
     * record (A / AAAA / CNAME chain) that ends at $expectedTarget. Native
     * dns_get_record is used so propagation is observed at the platform's
     * local resolver, not from inside the provider's API.
     */
    public function resolves(string $fqdn, string $expectedTarget): bool
    {
        $records = @dns_get_record($fqdn, DNS_CNAME + DNS_A + DNS_AAAA);
        if (! is_array($records) || $records === []) {
            return false;
        }

        $expected = strtolower(rtrim($expectedTarget, '.'));
        foreach ($records as $r) {
            $candidates = [
                $r['target'] ?? null,
                $r['ip'] ?? null,
                $r['ipv6'] ?? null,
            ];
            foreach ($candidates as $c) {
                if ($c !== null && strtolower(rtrim((string) $c, '.')) === $expected) {
                    return true;
                }
            }
        }

        return false;
    }
}
