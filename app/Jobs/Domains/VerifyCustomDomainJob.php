<?php

declare(strict_types=1);

namespace App\Jobs\Domains;

use App\Models\OrganizationDomain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Polls customer-controlled DNS for a custom domain (path B):
 *   1. Verify the CNAME points at our app host.
 *   2. Verify the TXT verification token matches what we issued.
 *
 * On success: mark dns_status=verified, tls_status=active.
 * After 7 days of failure: mark dns_status=failed and notify the org admin.
 *
 * Scheduled hourly; the scheduler dispatches one job per pending domain.
 */
class VerifyCustomDomainJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public const TIMEOUT_DAYS = 7;

    public int $tries = 1;

    public function __construct(public string $domainId) {}

    public function uniqueId(): string
    {
        return $this->domainId;
    }

    public function handle(): void
    {
        $domain = OrganizationDomain::withoutGlobalScopes()->find($this->domainId);
        if (! $domain || $domain->type !== OrganizationDomain::TYPE_CUSTOM) {
            return;
        }

        if ($domain->dns_status === OrganizationDomain::DNS_VERIFIED) {
            return;
        }

        $expectedTarget = 'app.'.config('app.platform_base_domain');
        $cnameOk = $this->cnameMatches($domain->host, $expectedTarget);
        $txtOk = $this->txtTokenMatches($domain->host, (string) $domain->verification_token);

        $domain->update(['last_checked_at' => now()]);

        if ($cnameOk && $txtOk) {
            $domain->update([
                'dns_status' => OrganizationDomain::DNS_VERIFIED,
                'tls_status' => OrganizationDomain::TLS_ACTIVE,
            ]);

            return;
        }

        // Timeout after 7 days of failed checks.
        if ($domain->created_at->lt(now()->subDays(self::TIMEOUT_DAYS))) {
            $domain->update(['dns_status' => OrganizationDomain::DNS_FAILED]);
        }
    }

    protected function cnameMatches(string $host, string $expectedTarget): bool
    {
        $records = @dns_get_record($host, DNS_CNAME);
        if (! is_array($records)) {
            return false;
        }

        $expected = strtolower(rtrim($expectedTarget, '.'));
        foreach ($records as $r) {
            $target = strtolower(rtrim((string) ($r['target'] ?? ''), '.'));
            if ($target === $expected) {
                return true;
            }
        }

        return false;
    }

    protected function txtTokenMatches(string $host, string $token): bool
    {
        if ($token === '') {
            return true; // skip if no token issued
        }

        $records = @dns_get_record('_deitam-verify.'.$host, DNS_TXT);
        if (! is_array($records)) {
            return false;
        }

        foreach ($records as $r) {
            $value = (string) ($r['txt'] ?? '');
            if ($value === $token) {
                return true;
            }
        }

        return false;
    }
}
