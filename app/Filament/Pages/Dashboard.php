<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PendingApprovalWidget;
use App\Notifications\SendEmailVerificationCode;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $routePath = '/';

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        /*
         * If the user has not verified their email yet,
         * do not allow them to view the dashboard.
         * Send/re-send OTP if missing or expired, then redirect to OTP page.
         */
        if (! $user->email_verified_at) {
            if (
                ! $user->email_verification_code ||
                ! $user->email_verification_code_expires_at ||
                now()->greaterThan($user->email_verification_code_expires_at)
            ) {
                $code = (string) random_int(100000, 999999);

                $user->forceFill([
                    'email_verification_code' => $code,
                    'email_verification_code_expires_at' => now()->addMinutes(10),
                ])->saveQuietly();

                $user->notify(new SendEmailVerificationCode($code));
            }

            $this->redirect(VerifyEmailCode::getUrl());

            return;
        }
    }

    protected function isPendingNormalUser(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (
            method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()
        ) {
            return false;
        }

        if ($user->hasAnyRole([
            'Super Admin',
            'super_admin',
            'Admin',
            'admin',
            'Admin Unit',
            'Staff Unit',
        ])) {
            return false;
        }

        return ! (bool) $user->is_active;
    }

    public function getWidgets(): array
    {
        if ($this->isPendingNormalUser()) {
            return [
                PendingApprovalWidget::class,
            ];
        }

        return Filament::getWidgets();
    }

    public function getColumns(): int|string|array
    {
        if ($this->isPendingNormalUser()) {
            return 1;
        }

        return parent::getColumns();
    }
}