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
 * End-user portal at /portal. Branded per-org. Surface area is intentionally
 * narrow: My Tickets / My Requests / My Assets / Profile. All resources are
 * filtered to the authenticated user's own data.
 *
 * Access is granted to any authenticated user who is a member of an
 * organization — gated by User::canAccessPanel('portal').
 */
class PortalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('portal')
            ->path('portal')
            ->login()
            ->colors(['primary' => Color::Indigo])
            ->brandName('Self-service portal')
            ->discoverResources(in: app_path('Filament/Portal/Resources'), for: 'App\\Filament\\Portal\\Resources')
            ->discoverPages(in: app_path('Filament/Portal/Pages'), for: 'App\\Filament\\Portal\\Pages')
            ->pages([Pages\Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Portal/Widgets'), for: 'App\\Filament\\Portal\\Widgets')
            ->widgets([Widgets\AccountWidget::class])
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
            ])
            ->authMiddleware([Authenticate::class])
            ->sidebarCollapsibleOnDesktop();
    }
}
