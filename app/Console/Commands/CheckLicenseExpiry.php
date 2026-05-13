<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use App\Notifications\LicenseExpiringSoon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

/**
 * Daily job: notifies Org Admins about licenses expiring within N days,
 * and about expired licenses that haven't been renewed yet.
 *
 * Add to console scheduling:
 *   ->command('licenses:check-expiry')->dailyAt('07:00');
 */
class CheckLicenseExpiry extends Command
{
    protected $signature = 'licenses:check-expiry
                            {--days=30 : Threshold in days for "expiring soon"}
                            {--dry-run : Don\'t send notifications, just count}';

    protected $description = 'Notify org admins about licenses expiring soon or already expired.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dryRun = (bool) $this->option('dry-run');

        $threshold = now()->addDays($days);

        $totals = ['expiring_soon' => 0, 'expired' => 0, 'orgs_notified' => 0];

        Organization::query()
            ->withoutGlobalScopes()
            ->chunk(50, function ($orgs) use ($threshold, $dryRun, &$totals) {
                foreach ($orgs as $org) {
                    $licenses = Asset::query()
                        ->withoutGlobalScopes([OrganizationScope::class])
                        ->where('organization_id', $org->id)
                        ->where('tracking_mode', AssetCategory::TRACKING_LICENSE)
                        ->whereIn('status', [
                            Asset::STATUS_IN_STOCK,
                            Asset::STATUS_DEPLOYED,
                        ])
                        ->whereNotNull('expiry_date')
                        ->where('expiry_date', '<=', $threshold)
                        ->get();

                    if ($licenses->isEmpty()) {
                        continue;
                    }

                    $expiring = $licenses->filter(fn (Asset $l) => $l->expiry_date->isFuture());
                    $expired = $licenses->filter(fn (Asset $l) => $l->expiry_date->isPast());

                    $totals['expiring_soon'] += $expiring->count();
                    $totals['expired'] += $expired->count();

                    if ($dryRun) {
                        $this->line(sprintf(
                            'Org %s: %d expiring, %d expired',
                            $org->slug,
                            $expiring->count(),
                            $expired->count(),
                        ));

                        continue;
                    }

                    // Notify every user with the Org Admin default role on this org.
                    $admins = $org->users()
                        ->wherePivot('default_role', 'Org Admin')
                        ->get();

                    if ($admins->isNotEmpty()) {
                        Notification::send($admins, new LicenseExpiringSoon($org, $expiring, $expired));
                        $totals['orgs_notified']++;
                    }
                }
            });

        $this->info(sprintf(
            'Done. expiring_soon=%d, expired=%d, orgs_notified=%d%s',
            $totals['expiring_soon'],
            $totals['expired'],
            $totals['orgs_notified'],
            $dryRun ? ' (dry run — no notifications sent)' : '',
        ));

        return self::SUCCESS;
    }
}
