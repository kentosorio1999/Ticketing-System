<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentTicketsTable extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected function getTableHeading(): string
    {
        $user = auth()->user();

        if ($user->hasRole('Admin Unit')) {
            return 'Recent Unit Tickets';
        }

        if ($user->hasRole('Staff Unit')) {
            return 'My Assigned Tickets';
        }

        if ($user->hasRole('User')) {
            return 'My Recent Tickets';
        }

        return 'Recent Tickets';
    }

    protected function getTableQuery(): Builder
    {
        $user = auth()->user();

        $query = Ticket::query()
            ->with([
                'owner',
                'unit',
                'category',
                'priority',
                'ticketStatus',
                'responsible',
            ])
            ->latest();

        if ($user->hasRole('Admin Unit')) {
            $query->where('unit_id', $user->unit_id);
        }

        if ($user->hasRole('Staff Unit')) {
            $query->where('responsible_id', $user->id);
        }

        if ($user->hasRole('User')) {
            $query->where('owner_id', $user->id);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Ticket')
                    ->searchable()
                    ->limit(30)
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable(),

                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit')
                    ->searchable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('priority.name')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (string | null $state): string => match ($state) {
                        'Low' => 'gray',
                        'Normal' => 'info',
                        'Medium' => 'warning',
                        'High' => 'danger',
                        'Urgent' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('ticketStatus.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string | null $state): string => match ($state) {
                        'New' => 'info',
                        'Open' => 'success',
                        'In Progress' => 'warning',
                        'Pending' => 'gray',
                        'Resolved' => 'primary',
                        'Closed' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('responsible.name')
                    ->label('Assigned To')
                    ->placeholder('Unassigned')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Ticket $record): string => TicketResource::getUrl('view', ['record' => $record])),
            ])
            ->defaultPaginationPageOption(5);
    }
}