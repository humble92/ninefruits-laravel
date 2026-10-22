<?php

use App\Providers\AppServiceProvider;
use App\Providers\DatabaseServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
    DatabaseServiceProvider::class,
    FortifyServiceProvider::class,
    AdminPanelProvider::class,
    HorizonServiceProvider::class,
];
