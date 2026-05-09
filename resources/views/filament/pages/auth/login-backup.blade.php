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
            '<div class="gov-login-subtitle">
                <strong>DICT Region VII - MISS</strong><br>
                ServiceDesk Support Management System<br>
                <span>Secure access for authorized personnel only.</span>
            </div>'
        );
    }

    protected function getRedirectUrl(): string
    {
        $user = auth()->user();

        if (! $user) {
            return Filament::getUrl();
        }

        // Pending account: send only to dashboard.
        // Your Dashboard.php should show PendingApprovalWidget only.
        if (
            array_key_exists('is_active', $user->getAttributes())
            && ! (bool) $user->is_active
        ) {
            return Filament::getUrl();
        }

        $roles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->map(fn ($role) => strtolower($role))->toArray()
            : [];

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return Filament::getUrl();
        }

        if (in_array('super admin', $roles) || in_array('admin', $roles)) {
            return Filament::getUrl();
        }

        if (in_array('staff', $roles) || in_array('user', $roles)) {
            return $this->getTicketsUrl();
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