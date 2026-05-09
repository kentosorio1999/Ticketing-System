<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Resources\TicketResource;
use Filament\Facades\Filament;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class Login extends BaseLogin
{
    public function getHeading(): string | Htmlable
    {
        return 'Sign in to your account';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return new HtmlString(
            '<div style="text-align: center; line-height: 1.6;">
                <strong style="color: #1d4ed8;">DICT Region VII - MISS</strong><br>
                <span>ServiceDesk Support Management System</span><br>
                <small style="color: #64748b;">Secure access for authorized personnel only.</small>
            </div>'
        );
    }

    protected function getRedirectUrl(): string
    {
        $user = auth()->user();

        if (! $user) {
            return Filament::getUrl();
        }

        /*
        |--------------------------------------------------------------------------
        | Pending Account
        |--------------------------------------------------------------------------
        | If user is not active, redirect only to dashboard.
        | Your Dashboard.php should control what pending users can see.
        */
        if (
            array_key_exists('is_active', $user->getAttributes())
            && ! (bool) $user->is_active
        ) {
            return Filament::getUrl();
        }

        /*
        |--------------------------------------------------------------------------
        | Super Admin / Admin
        |--------------------------------------------------------------------------
        | Full dashboard access.
        */
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('Super Admin')) {
                return Filament::getUrl();
            }

            if ($user->hasRole('Admin')) {
                return Filament::getUrl();
            }

            /*
            |--------------------------------------------------------------------------
            | Staff / User
            |--------------------------------------------------------------------------
            | Redirect to tickets page.
            */
            if ($user->hasRole('Staff')) {
                return $this->getTicketsUrl();
            }

            if ($user->hasRole('User')) {
                return $this->getTicketsUrl();
            }
        }

        return Filament::getUrl();
    }

    private function getTicketsUrl(): string
    {
        try {
            return TicketResource::getUrl('index');
        } catch (\Throwable $e) {
            return Filament::getUrl();
        }
    }
}