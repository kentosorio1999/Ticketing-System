<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\ConfigServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use SocialiteProviders\Manager\ServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    // App\Providers\BroadcastServiceProvider::class,
    EventServiceProvider::class,
    AdminPanelProvider::class,
    // App\Providers\RouteServiceProvider::class,
    ConfigServiceProvider::class,
    ServiceProvider::class,
];
