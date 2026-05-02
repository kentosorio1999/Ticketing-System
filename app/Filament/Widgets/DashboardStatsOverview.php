<?php

namespace App\Filament\Widgets;

use App\Models\Ticket;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStatsOverview extends BaseWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $user = auth()->user();

        // SUPER ADMIN
        if ($this->isSuperAdmin($user)) {
            return [
                Stat::make('Total Users', User::count())
                    ->description('Registered system users')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('info'),

                Stat::make('Total Tickets', Ticket::count())
                    ->description('All submitted tickets')
                    ->descriptionIcon('heroicon-m-ticket')
                    ->color('warning'),

                Stat::make('Open Tickets', $this->countTicketsByStatus('Open'))
                    ->description('Tickets waiting for action')
                    ->descriptionIcon('heroicon-m-folder-open')
                    ->color('success'),

                Stat::make('Resolved Tickets', $this->countTicketsByStatus('Resolved'))
                    ->description('Resolved tickets')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('primary'),
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

                Stat::make('Ongoing Unit Tickets', $this->countUnitTicketsByStatus($user->unit_id, 'Ongoing'))
                    ->description('Ongoing tickets in your unit')
                    ->descriptionIcon('heroicon-m-arrow-path')
                    ->color('info'),

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

                Stat::make('Ongoing Assigned', $this->countAssignedTicketsByStatus($user->id, 'Ongoing'))
                    ->description('Tickets you are handling')
                    ->descriptionIcon('heroicon-m-arrow-path')
                    ->color('info'),

                Stat::make('Resolved Assigned', $this->countAssignedTicketsByStatus($user->id, 'Resolved'))
                    ->description('Tickets you resolved')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('primary'),
            ];
        }

        // NORMAL USER
        return [
            Stat::make('My Tickets', Ticket::where('owner_id', $user->id)->count())
                ->description('Tickets you submitted')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('warning'),

            Stat::make('My Open Tickets', $this->countOwnedTicketsByStatus($user->id, 'Open'))
                ->description('Your open tickets')
                ->descriptionIcon('heroicon-m-folder-open')
                ->color('success'),

            Stat::make('My Ongoing Tickets', $this->countOwnedTicketsByStatus($user->id, 'Ongoing'))
                ->description('Tickets being handled')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),

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

    private function countOwnedTicketsByStatus(int $userId, string $status): int
    {
        return Ticket::where('owner_id', $userId)
            ->whereHas('ticketStatus', function ($query) use ($status) {
                $query->where('name', $status);
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

    private function countUnitTicketsByStatus(int $unitId, string $status): int
    {
        return Ticket::where('unit_id', $unitId)
            ->whereHas('ticketStatus', function ($query) use ($status) {
                $query->where('name', $status);
            })
            ->count();
    }

    private function isSuperAdmin($user): bool
    {
        return method_exists($user, 'isSuperAdmin')
            ? $user->isSuperAdmin()
            : $user->hasRole('Super Admin');
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