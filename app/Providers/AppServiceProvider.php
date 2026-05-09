<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use BezhanSalleh\FilamentLanguageSwitch\LanguageSwitch;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);

        if (env('LIVEWIRE_BASE_PATH')) {
            Livewire::setScriptRoute(function ($handle) {
                return Route::get(env('LIVEWIRE_BASE_PATH') . '/vendor/livewire.js', $handle);
            });

            Livewire::setUpdateRoute(function ($handle) {
                return Route::get(env('LIVEWIRE_BASE_PATH') . '/update', $handle);
            });
        }

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $switch->locales(['en', 'tl']);
        });

        FilamentView::registerRenderHook(
            'panels::auth.login.form.after',
            fn (): string => view('filament.auth.login-security')->render()
        );

        FilamentView::registerRenderHook(
            'panels::head.end',
            fn (): string => view('filament.auth.login-styles')->render()
        );
    }
}