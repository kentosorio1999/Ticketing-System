<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsOverview extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    /**
     * Hide ticket stats from pending users.
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

    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        /**
         * Pending normal users should not see ticket statistics.
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

        // SUPER ADMIN
        if ($this->isSuperAdmin($user)) {
            return [
                Stat::make('New Tickets', $this->countTicketsByStatus('New'))
                    ->description('Newly submitted tickets')
                    ->descriptionIcon('heroicon-m-plus-circle')
                    ->color('info'),

                Stat::make('Total Tickets', Ticket::count())
                    ->description('All submitted tickets')
                    ->descriptionIcon('heroicon-m-ticket')
                    ->color('warning'),

                Stat::make('Open Tickets', $this->countTicketsByStatus('Open'))
                    ->description('Tickets waiting for action')
                    ->descriptionIcon('heroicon-m-folder-open')
                    ->color('success'),

                Stat::make('Urgent Tickets', $this->countTicketsByPriority('Urgent'))
                    ->description('Tickets needing immediate action')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),

                Stat::make('Resolved Tickets', $this->countTicketsByStatus('Resolved'))
                    ->description('Resolved tickets')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('primary'),

                Stat::make('Total Users', User::count())
                    ->description('Registered system users')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('gray'),
            ];
        }

        // ADMIN UNIT
        if ($this->isAdminUnit($user)) {
            return [
                Stat::make('Unit Tickets', Ticket::where('unit_id', $user->unit_id)->count())
                    ->description('All tickets in your unit')
                    ->descriptionIcon('heroicon-m-ticket')
                    ->color('warning'),

                Stat::make('Open Unit Tickets', $this->countUnitTicketsByStatus($user->unit_id, 'Open'))
                    ->description('Open tickets in your unit')
                    ->descriptionIcon('heroicon-m-folder-open')
                    ->color('success'),

                Stat::make('In Progress Unit Tickets', $this->countUnitTicketsByStatus($user->unit_id, 'In Progress'))
                    ->description('Tickets currently being handled')
                    ->descriptionIcon('heroicon-m-arrow-path')
                    ->color('info'),

                Stat::make('Urgent Unit Tickets', $this->countUnitTicketsByPriority($user->unit_id, 'Urgent'))
                    ->description('Urgent tickets in your unit')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),

                Stat::make('Resolved Unit Tickets', $this->countUnitTicketsByStatus($user->unit_id, 'Resolved'))
                    ->description('Resolved tickets in your unit')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('primary'),
            ];
        }

        // STAFF UNIT
        if ($this->isStaffUnit($user)) {
            return [
                Stat::make('Assigned Tickets', Ticket::where('responsible_id', $user->id)->count())
                    ->description('Tickets assigned to you')
                    ->descriptionIcon('heroicon-m-ticket')
                    ->color('warning'),

                Stat::make('Open Assigned', $this->countAssignedTicketsByStatus($user->id, 'Open'))
                    ->description('Your open assigned tickets')
                    ->descriptionIcon('heroicon-m-folder-open')
                    ->color('success'),

                Stat::make('In Progress Assigned', $this->countAssignedTicketsByStatus($user->id, 'In Progress'))
                    ->description('Tickets you are handling')
                    ->descriptionIcon('heroicon-m-arrow-path')
                    ->color('info'),

                Stat::make('Urgent Assigned', $this->countAssignedTicketsByPriority($user->id, 'Urgent'))
                    ->description('Urgent tickets assigned to you')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),

                Stat::make('Resolved Assigned', $this->countAssignedTicketsByStatus($user->id, 'Resolved'))
                    ->description('Tickets you resolved')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('primary'),
            ];
        }

        // NORMAL APPROVED USER
        return [
            Stat::make('My Tickets', Ticket::where('owner_id', $user->id)->count())
                ->description('Tickets you submitted')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('warning'),

            Stat::make('My Open Tickets', $this->countOwnedTicketsByStatus($user->id, 'Open'))
                ->description('Your open tickets')
                ->descriptionIcon('heroicon-m-folder-open')
                ->color('success'),

            Stat::make('My In Progress Tickets', $this->countOwnedTicketsByStatus($user->id, 'In Progress'))
                ->description('Tickets being handled')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),

            Stat::make('My Urgent Tickets', $this->countOwnedTicketsByPriority($user->id, 'Urgent'))
                ->description('Your urgent tickets')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('My Resolved Tickets', $this->countOwnedTicketsByStatus($user->id, 'Resolved'))
                ->description('Your resolved tickets')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('primary'),
        ];
    }

    private function countTicketsByStatus(string $status): int
    {
        return Ticket::whereHas('ticketStatus', function ($query) use ($status) {
            $query->where('name', $status);
        })->count();
    }

    private function countTicketsByPriority(string $priority): int
    {
        return Ticket::whereHas('priority', function ($query) use ($priority) {
            $query->where('name', $priority);
        })->count();
    }

    private function countOwnedTicketsByStatus(int $userId, string $status): int
    {
        return Ticket::where('owner_id', $userId)
            ->whereHas('ticketStatus', function ($query) use ($status) {
                $query->where('name', $status);
            })
            ->count();
    }

    private function countOwnedTicketsByPriority(int $userId, string $priority): int
    {
        return Ticket::where('owner_id', $userId)
            ->whereHas('priority', function ($query) use ($priority) {
                $query->where('name', $priority);
            })
            ->count();
    }

    private function countAssignedTicketsByStatus(int $userId, string $status): int
    {
        return Ticket::where('responsible_id', $userId)
            ->whereHas('ticketStatus', function ($query) use ($status) {
                $query->where('name', $status);
            })
            ->count();
    }

    private function countAssignedTicketsByPriority(int $userId, string $priority): int
    {
        return Ticket::where('responsible_id', $userId)
            ->whereHas('priority', function ($query) use ($priority) {
                $query->where('name', $priority);
            })
            ->count();
    }

    private function countUnitTicketsByStatus(?int $unitId, string $status): int
    {
        if (! $unitId) {
            return 0;
        }

        return Ticket::where('unit_id', $unitId)
            ->whereHas('ticketStatus', function ($query) use ($status) {
                $query->where('name', $status);
            })
            ->count();
    }

    private function countUnitTicketsByPriority(?int $unitId, string $priority): int
    {
        if (! $unitId) {
            return 0;
        }

        return Ticket::where('unit_id', $unitId)
            ->whereHas('priority', function ($query) use ($priority) {
                $query->where('name', $priority);
            })
            ->count();
    }

    private function isSuperAdmin($user): bool
    {
        return method_exists($user, 'isSuperAdmin')
            ? $user->isSuperAdmin()
            : $user->hasAnyRole(['Super Admin', 'super_admin']);
    }

    private function isAdminUnit($user): bool
    {
        return $user->hasRole('Admin Unit');
    }

    private function isStaffUnit($user): bool
    {
        return $user->hasRole('Staff Unit');
    }
}