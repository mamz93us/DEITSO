<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AppPanelProvider;
use App\Providers\Filament\PortalPanelProvider;
use App\Providers\Filament\SystemPanelProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    SystemPanelProvider::class,
    AppPanelProvider::class,
    PortalPanelProvider::class,
    HorizonServiceProvider::class,
];
