<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class PendingApprovalWidget extends Widget
{
    protected static string $view = 'filament.widgets.pending-approval-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Never show pending cards to Super Admin/Admin/Staff users
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

        // Show only to newly created normal users waiting for approval
        return ! (bool) $user->is_active;
    }
}
