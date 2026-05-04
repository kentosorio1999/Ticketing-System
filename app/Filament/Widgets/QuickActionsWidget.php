<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Reports;
use App\Filament\Resources\TicketResource;
use App\Filament\Resources\UserResource;
use Filament\Widgets\Widget;

class QuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quick-actions-widget';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

    /**
     * Hide this widget from pending users.
     */
    public static function canView(): bool
    {
        $user = auth()->user();

        if (! $user) {
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
            return true;
        }

        return (bool) $user->is_active;
    }

    public function getUserRole(): string
    {
        $user = auth()->user();

        if (! $user) {
            return '';
        }

        if (! $user->is_active && ! $user->hasAnyRole([
            'Super Admin',
            'super_admin',
            'Admin',
            'admin',
            'Admin Unit',
            'Staff Unit',
        ])) {
            return 'Pending User';
        }

        if ($user->isSuperAdmin()) {
            return 'Super Admin';
        }

        return $user->roles->pluck('name')->first() ?? 'User';
    }

    public function getAvatarUrl(): ?string
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        return method_exists($user, 'getFilamentAvatarUrl')
            ? $user->getFilamentAvatarUrl()
            : null;
    }

    public function getInitials(): string
    {
        $name = auth()->user()?->name ?? 'User';

        return collect(explode(' ', $name))
            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
            ->take(2)
            ->implode('');
    }

    public function getLogoutUrl(): string
    {
        return route('filament.admin.auth.logout');
    }

    public function getQuickActions(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        /**
         * Pending users should not see Create Ticket or My Tickets.
         */
        if (
            ! $user->is_active &&
            ! $user->hasAnyRole([
                'Super Admin',
                'super_admin',
                'Admin',
                'admin',
                'Admin Unit',
                'Staff Unit',
            ])
        ) {
            return [];
        }

        if ($user->isSuperAdmin() || $user->hasAnyRole(['Super Admin', 'super_admin'])) {
            return [
                [
                    'label' => 'View Tickets',
                    'description' => 'Review and manage all tickets',
                    'icon' => 'heroicon-o-ticket',
                    'url' => TicketResource::getUrl('index'),
                ],
                [
                    'label' => 'Create Ticket',
                    'description' => 'Create a new support ticket',
                    'icon' => 'heroicon-o-plus-circle',
                    'url' => TicketResource::getUrl('create'),
                ],
                [
                    'label' => 'Manage Users',
                    'description' => 'View and manage system users',
                    'icon' => 'heroicon-o-users',
                    'url' => UserResource::getUrl('index'),
                ],
                [
                    'label' => 'Reports',
                    'description' => 'View ticket reports',
                    'icon' => 'heroicon-o-chart-bar-square',
                    'url' => Reports::getUrl(),
                ],
            ];
        }

        if ($user->hasRole('Admin Unit')) {
            return [
                [
                    'label' => 'View Unit Tickets',
                    'description' => 'Review tickets in your unit',
                    'icon' => 'heroicon-o-ticket',
                    'url' => TicketResource::getUrl('index'),
                ],
                [
                    'label' => 'Create Ticket',
                    'description' => 'Create a new support ticket',
                    'icon' => 'heroicon-o-plus-circle',
                    'url' => TicketResource::getUrl('create'),
                ],
                [
                    'label' => 'Reports',
                    'description' => 'View unit reports',
                    'icon' => 'heroicon-o-chart-bar-square',
                    'url' => Reports::getUrl(),
                ],
            ];
        }

        if ($user->hasRole('Staff Unit')) {
            return [
                [
                    'label' => 'Assigned Tickets',
                    'description' => 'View tickets assigned to you',
                    'icon' => 'heroicon-o-clipboard-document-check',
                    'url' => TicketResource::getUrl('index'),
                ],
            ];
        }

        /**
         * Normal approved user.
         */
        return [
            [
                'label' => 'Create Ticket',
                'description' => 'Submit a new request',
                'icon' => 'heroicon-o-plus-circle',
                'url' => TicketResource::getUrl('create'),
            ],
            [
                'label' => 'My Tickets',
                'description' => 'Track your submitted tickets',
                'icon' => 'heroicon-o-ticket',
                'url' => TicketResource::getUrl('index'),
            ],
        ];
    }
}
