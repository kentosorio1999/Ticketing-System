<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\PendingApprovalWidget;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $routePath = '/';

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
