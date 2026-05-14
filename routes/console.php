<?php

declare(strict_types=1);

use App\Jobs\Domains\VerifyCustomDomainJob;
use App\Models\OrganizationDomain;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Daily: license expiry alerts.
Schedule::command('licenses:check-expiry')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->onOneServer();

// Every 5 minutes: ticket SLA breach detection + notification.
Schedule::command('tickets:check-sla-breaches')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// Daily: stuck HR task flag.
Schedule::command('hr:check-stuck-tasks')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer();

// Hourly: custom-domain DNS re-verification for pending domains.
Schedule::call(function () {
    OrganizationDomain::query()
        ->withoutGlobalScopes()
        ->where('type', OrganizationDomain::TYPE_CUSTOM)
        ->where('dns_status', '!=', OrganizationDomain::DNS_VERIFIED)
        ->where('created_at', '>', now()->subDays(7))
        ->chunk(50, function ($domains) {
            foreach ($domains as $d) {
                VerifyCustomDomainJob::dispatch($d->id);
            }
        });
})->hourly()->name('custom-domains:verify')->withoutOverlapping();
