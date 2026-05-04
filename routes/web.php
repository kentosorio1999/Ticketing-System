<?php

use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// When opening http://127.0.0.1:8000, go to Filament login
Route::get('/', function () {
    return redirect('/admin/login');
});

// Socialite login routes
Route::get('/auth/{provider}', [SocialiteController::class, 'redirectToProvider'])
    ->name('socialite.redirect');

Route::get('/auth/{provider}/callback', [SocialiteController::class, 'handleProvideCallback'])
    ->name('socialite.callback');

// Leave impersonation
Route::get('/admin/leave-impersonation', function (Request $request) {
    if (app()->bound('impersonate') && app('impersonate')->isImpersonating()) {
        app('impersonate')->leave();
    }

    $request->session()->regenerateToken();

    return redirect('/admin?left_impersonation=1');
})->name('admin.leave-impersonation');
