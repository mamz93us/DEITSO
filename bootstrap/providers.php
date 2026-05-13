<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AppPanelProvider;
use App\Providers\Filament\SystemPanelProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    SystemPanelProvider::class,
    AppPanelProvider::class,
    HorizonServiceProvider::class,
];
