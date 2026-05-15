<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Http\Middleware\ApplyOrganizationBranding;
use App\Http\Middleware\ResolveOrganizationFromHost;
use App\Http\Middleware\SetActiveOrganization;
use App\Http\Middleware\SetSpatieTeamContext;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Technician panel at /technician. Access requires `is_technician = true` (or
 * `is_system_admin = true`). Technicians are cross-tenant by design — they
 * service many organizations' tickets, visits, and requests from a single pane.
 *
 * The OrganizationScope explicitly treats technicians as a span-all-orgs
 * context (same as system admins), so resources here can use the standard
 * Eloquent query and still see every org's data.
 */
class TechnicianPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('technician')
            ->path('technician')
            ->login()
            ->colors([
                'primary' => Color::Cyan,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'gray' => Color::Slate,
            ])
            ->font('Inter')
            ->brandName('Technician Workbench')
            ->discoverResources(in: app_path('Filament/Technician/Resources'), for: 'App\\Filament\\Technician\\Resources')
            ->discoverPages(in: app_path('Filament/Technician/Pages'), for: 'App\\Filament\\Technician\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Technician/Widgets'), for: 'App\\Filament\\Technician\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                ResolveOrganizationFromHost::class,
                SetActiveOrganization::class,
                SetSpatieTeamContext::class,
                ApplyOrganizationBranding::class,
                'throttle:filament-auth',
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->sidebarCollapsibleOnDesktop();
    }
}
