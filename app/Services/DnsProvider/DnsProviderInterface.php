<?php

declare(strict_types=1);

namespace App\Services\DnsProvider;

interface DnsProviderInterface
{
    /**
     * Create a CNAME (or A) record pointing $name under the provider's
     * base domain at $target. Returns the provider's raw response for audit.
     *
     * @return array<string, mixed>
     */
    public function createSubdomain(string $name, string $target): array;

    /**
     * Return true if a record for $name exists at the provider.
     */
    public function recordExists(string $name): bool;

    /**
     * Return true if $fqdn resolves (via DNS, not the provider's API) to
     * $expectedTarget. Used to confirm propagation before issuing certs.
     */
    public function resolves(string $fqdn, string $expectedTarget): bool;

    /**
     * Remove the record for $name. Idempotent: removing a missing record
     * must not throw.
     */
    public function removeSubdomain(string $name): void;
}
