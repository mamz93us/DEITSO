<?php

declare(strict_types=1);

namespace App\Jobs\Domains;

use App\Models\DnsProviderAccount;
use App\Models\Organization;
use App\Models\OrganizationDomain;
use App\Services\DnsProvider\DnsProviderManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Provision a platform subdomain for an organization end-to-end:
 *
 *   1. Create the CNAME at the DNS provider (idempotent — recordExists short-circuit).
 *   2. Poll DNS until it resolves to the target (or fail after retries).
 *   3. Mark the OrganizationDomain row dns_status=verified, tls_status=active.
 *
 * Caddy picks up cert provisioning automatically on the next HTTPS hit because
 * the allow-list endpoint will start returning 200 for the host.
 *
 * Designed to be ShouldBeUnique on (organization_id, host) so dispatch storms
 * (UI double-clicks, retries) collapse to one in-flight run.
 */
class ProvisionOrganizationSubdomainJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(
        public string $organizationId,
        public string $host,
    ) {}

    public function uniqueId(): string
    {
        return $this->organizationId.':'.$this->host;
    }

    public function handle(DnsProviderManager $manager): void
    {
        $org = Organization::withoutGlobalScopes()->find($this->organizationId);
        if (! $org) {
            Log::warning('ProvisionOrganizationSubdomainJob: organization not found', [
                'organization_id' => $this->organizationId,
            ]);

            return;
        }

        $domain = OrganizationDomain::withoutGlobalScopes()
            ->where('organization_id', $this->organizationId)
            ->where('host', $this->host)
            ->first();

        if (! $domain) {
            Log::warning('ProvisionOrganizationSubdomainJob: domain row not found', [
                'organization_id' => $this->organizationId,
                'host' => $this->host,
            ]);

            return;
        }

        $account = $domain->dnsProviderAccount ?? DnsProviderAccount::where('is_default', true)->first();
        if (! $account) {
            $domain->update(['dns_status' => OrganizationDomain::DNS_FAILED]);
            Log::error('ProvisionOrganizationSubdomainJob: no DNS provider configured');

            return;
        }

        $provider = $manager->forAccount($account);
        $base = $account->base_domain;
        $name = $this->extractSubdomainName($this->host, $base);
        $target = 'app.'.$base;

        // Step 1 — create CNAME (idempotent).
        if (! $provider->recordExists($name)) {
            $provider->createSubdomain($name, $target);
        }

        // Step 2 — poll DNS until propagated, with bounded back-off.
        $verified = false;
        $attempts = 20;
        $delaySeconds = 30;
        for ($i = 0; $i < $attempts; $i++) {
            if ($provider->resolves($this->host, $target)) {
                $verified = true;
                break;
            }
            sleep($delaySeconds);
        }

        if (! $verified) {
            $domain->update([
                'dns_status' => OrganizationDomain::DNS_FAILED,
                'last_checked_at' => now(),
            ]);

            return;
        }

        $domain->update([
            'dns_status' => OrganizationDomain::DNS_VERIFIED,
            'tls_status' => OrganizationDomain::TLS_ACTIVE,
            'last_checked_at' => now(),
        ]);
    }

    protected function extractSubdomainName(string $host, string $base): string
    {
        $suffix = '.'.$base;
        if (str_ends_with($host, $suffix)) {
            return substr($host, 0, -strlen($suffix));
        }

        return $host;
    }
}
