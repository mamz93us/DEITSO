<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Jobs\Domains\VerifyCustomDomainJob;
use App\Models\OrganizationDomain;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

/**
 * Step-by-step setup guide for custom client domains. Shows DNS records to
 * create, polls verification status, and surfaces the next steps clearly.
 * Used as a sub-page of the OrganizationDomainResource workflow.
 */
class DomainSetupGuide extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 25;

    protected static ?string $navigationLabel = 'Custom domain setup';

    protected static string $view = 'filament.app.pages.domain-setup-guide';

    public function getTitle(): string
    {
        return 'Custom domain setup guide';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCustomDomains(): array
    {
        return OrganizationDomain::query()
            ->where('type', OrganizationDomain::TYPE_CUSTOM)
            ->orderBy('host')
            ->get()
            ->map(function (OrganizationDomain $d) {
                if ($d->verification_token === null) {
                    $d->update(['verification_token' => Str::random(40)]);
                }

                return [
                    'host' => $d->host,
                    'token' => $d->verification_token,
                    'dns_status' => $d->dns_status,
                    'tls_status' => $d->tls_status,
                    'cname_target' => 'app.'.config('app.platform_base_domain'),
                    'txt_host' => '_deitam-verify.'.$d->host,
                ];
            })
            ->all();
    }

    public function reverify(string $domainId): void
    {
        $domain = OrganizationDomain::find($domainId);
        if (! $domain) {
            return;
        }

        VerifyCustomDomainJob::dispatchSync($domain->id);

        Notification::make()
            ->title('Verification queued')
            ->body('We\'ll re-check the DNS records now. The status will update within a minute.')
            ->success()
            ->send();
    }
}
