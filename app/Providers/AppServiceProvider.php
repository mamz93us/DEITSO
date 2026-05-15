<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Organization;
use App\Observers\OrganizationObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Organization::observe(OrganizationObserver::class);

        $this->configureRateLimiters();
    }

    /**
     * Named rate limiters. Each is applied via `throttle:<name>` middleware.
     *
     * - filament-auth: protects login/password-reset/email-verification paths
     *   across all Filament panels. Returns Limit::none() for non-auth paths so
     *   it can be applied to the panel's full middleware stack safely.
     * - internal-callback: extra layer on Caddy's on-demand TLS check (already
     *   gated by token + IP allowlist; this limits per-IP request rate).
     */
    protected function configureRateLimiters(): void
    {
        RateLimiter::for('filament-auth', function (Request $request) {
            if (Str::contains($request->path(), ['/login', '/password-reset', '/email-verification'])) {
                return Limit::perMinute(5)->by($request->ip());
            }

            return Limit::none();
        });

        RateLimiter::for('internal-callback', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
    }
}
