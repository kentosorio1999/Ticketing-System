<?php

use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/admin/leave-impersonation', function (Request $request) {
    if (app()->bound('impersonate') && app('impersonate')->isImpersonating()) {
        app('impersonate')->leave();
    }

    $request->session()->regenerateToken();

    return redirect('/admin');
})->middleware(['web', 'auth'])->name('admin.leave-impersonation');