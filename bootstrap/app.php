<?php

declare(strict_types=1);

use App\Http\Middleware\ApplyOrganizationBranding;
use App\Http\Middleware\IdempotencyKey;
use App\Http\Middleware\ResolveOrganizationFromHost;
use App\Http\Middleware\SetActiveOrganization;
use App\Http\Middleware\SetSpatieTeamContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(__DIR__.'/../routes/internal.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'resolve.org' => ResolveOrganizationFromHost::class,
            'set.org' => SetActiveOrganization::class,
            'team.context' => SetSpatieTeamContext::class,
            'apply.branding' => ApplyOrganizationBranding::class,
            'idempotency' => IdempotencyKey::class,
        ]);

        $middleware->web(append: [
            ResolveOrganizationFromHost::class,
            SetActiveOrganization::class,
            SetSpatieTeamContext::class,
            ApplyOrganizationBranding::class,
        ]);

        $middleware->api(prepend: [
            ResolveOrganizationFromHost::class,
            SetActiveOrganization::class,
            SetSpatieTeamContext::class,
            IdempotencyKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
