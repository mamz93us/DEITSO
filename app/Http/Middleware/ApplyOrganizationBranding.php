<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shares the active organization's branding with all views as $branding.
 *
 * Reads `organization_brandings` for the org bound to `current.organization`.
 * If the table doesn't exist (Sprint 0) or there's no active org, shares a
 * sensible default so blade templates don't blow up.
 */
class ApplyOrganizationBranding
{
    public function handle(Request $request, Closure $next): Response
    {
        $branding = $this->resolveBranding();
        View::share('branding', $branding);

        return $next($request);
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolveBranding(): array
    {
        $defaults = [
            'logo_path' => null,
            'favicon_path' => null,
            'primary_color' => '#0f172a',
            'secondary_color' => '#1e293b',
            'accent_color' => '#3b82f6',
            'email_header_html' => null,
            'email_footer_html' => null,
            'pdf_header_html' => null,
            'pdf_footer_html' => null,
            'portal_welcome_text' => null,
        ];

        if (! app()->bound('current.organization') || app('current.organization') === null) {
            return $defaults;
        }

        if (! Schema::hasTable('organization_brandings')) {
            return $defaults;
        }

        $org = app('current.organization');
        $orgId = is_object($org) ? $org->id : $org;

        $row = DB::table('organization_brandings')->where('organization_id', $orgId)->first();
        if (! $row) {
            return $defaults;
        }

        return array_merge($defaults, (array) $row);
    }
}
