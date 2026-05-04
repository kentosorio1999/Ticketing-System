<?php

namespace App\Filament\Pages;

use App\Models\Ticket;
use Filament\Pages\Page;

class Reports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.reports';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->hasAnyRole([
            'Super Admin',
            'Admin Unit',
        ]);
    }

    public function getReportStats(): array
    {
        $query = Ticket::query();

        if (auth()->user()->hasRole('Admin Unit')) {
            $query->where('unit_id', auth()->user()->unit_id);
        }

        return [
            'total' => (clone $query)->count(),
            'new' => (clone $query)->whereHas('ticketStatus', fn ($q) => $q->where('name', 'New'))->count(),
            'open' => (clone $query)->whereHas('ticketStatus', fn ($q) => $q->where('name', 'Open'))->count(),
            'in_progress' => (clone $query)->whereHas('ticketStatus', fn ($q) => $q->where('name', 'In Progress'))->count(),
            'pending' => (clone $query)->whereHas('ticketStatus', fn ($q) => $q->where('name', 'Pending'))->count(),
            'resolved' => (clone $query)->whereHas('ticketStatus', fn ($q) => $q->where('name', 'Resolved'))->count(),
            'closed' => (clone $query)->whereHas('ticketStatus', fn ($q) => $q->where('name', 'Closed'))->count(),
            'urgent' => (clone $query)->whereHas('priority', fn ($q) => $q->where('name', 'Urgent'))->count(),
            'unassigned' => (clone $query)->whereNull('responsible_id')->count(),
        ];
    }

    public function getTicketsByCategory(): array
    {
        $query = Ticket::query()
            ->selectRaw('categories.name as category, COUNT(tickets.id) as total')
            ->join('categories', 'categories.id', '=', 'tickets.category_id')
            ->groupBy('categories.name')
            ->orderByDesc('total');

        if (auth()->user()->hasRole('Admin Unit')) {
            $query->where('tickets.unit_id', auth()->user()->unit_id);
        }

        return $query->get()->toArray();
    }

    public function getTicketsByUnit(): array
    {
        $query = Ticket::query()
            ->selectRaw('units.name as unit, COUNT(tickets.id) as total')
            ->join('units', 'units.id', '=', 'tickets.unit_id')
            ->groupBy('units.name')
            ->orderByDesc('total');

        if (auth()->user()->hasRole('Admin Unit')) {
            $query->where('tickets.unit_id', auth()->user()->unit_id);
        }

        return $query->get()->toArray();
    }

    public function getRecentResolvedTickets(): array
    {
        $query = Ticket::query()
            ->with(['owner', 'unit', 'category', 'priority', 'ticketStatus'])
            ->whereHas('ticketStatus', fn ($q) => $q->where('name', 'Resolved'))
            ->latest()
            ->limit(5);

        if (auth()->user()->hasRole('Admin Unit')) {
            $query->where('unit_id', auth()->user()->unit_id);
        }

        return $query->get()->toArray();
    }
}