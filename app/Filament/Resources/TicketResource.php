<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TicketResource\Pages;
use App\Filament\Resources\TicketResource\RelationManagers\CommentsRelationManager;
use App\Models\Category;
use App\Models\Priority;
use App\Models\Ticket;
use App\Models\TicketStatus;
use App\Models\Unit;
use App\Models\User;
use App\Settings\GeneralSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static function getNavigationGroup(): ?string
    {
        return __('Ticket Management');
    }

    public static function getModelLabel(): string
    {
        return __('Ticket');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Tickets');
    }

    /**
     * Pending users cannot see ticket navigation, ticket pages, or create tickets.
     */
    protected static function userCanAccessTickets(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Admin roles can always access tickets.
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

        // Normal users can access tickets only after Super Admin approval.
        return (bool) $user->is_active;
    }

    /**
     * Hide Tickets menu for pending users.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::userCanAccessTickets();
    }

    /**
     * Block pending users from opening ticket list directly.
     */
    public static function canViewAny(): bool
    {
        return static::userCanAccessTickets();
    }

    /**
     * Block pending users from creating tickets directly.
     */
    public static function canCreate(): bool
    {
        return static::userCanAccessTickets();
    }

    /**
     * Extra protection for the whole resource.
     */
    public static function canAccess(): bool
    {
        return static::userCanAccessTickets();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Ticket Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('description')
                            ->label('Description')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Select::make('unit_id')
                            ->label('Unit')
                            ->options(Unit::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(Category::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('priority_id')
                            ->label('Priority')
                            ->options(Priority::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('ticket_statuses_id')
                            ->label('Status')
                            ->options(function () {
                                $user = auth()->user();

                                if ($user?->hasRole('Staff Unit')) {
                                    return TicketStatus::query()
                                        ->whereIn('name', [
                                            'In Progress',
                                            'Pending',
                                            'Resolved',
                                        ])
                                        ->orderBy('name')
                                        ->pluck('name', 'id');
                                }

                                return TicketStatus::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->visible(fn (): bool => auth()->user()?->hasAnyRole([
                                'Super Admin',
                                'Admin Unit',
                                'Staff Unit',
                            ]) ?? false),

                        Forms\Components\Select::make('responsible_id')
                            ->label('Assigned Staff')
                            ->options(
                                fn () => User::role('Staff Unit')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('Unassigned')
                            ->visible(fn (): bool => auth()->user()?->hasAnyRole([
                                'Super Admin',
                                'Admin Unit',
                            ]) ?? false),

                        Forms\Components\Select::make('owner_id')
                            ->label('Owner')
                            ->options(User::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->default(fn () => auth()->id())
                            ->visible(fn (): bool => auth()->user()?->hasAnyRole([
                                'Super Admin',
                                'Admin Unit',
                            ]) ?? false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Ticket')
                    ->limit(50)
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('priority.name')
                    ->label('Priority')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'Low' => 'gray',
                        'Normal' => 'info',
                        'Medium' => 'warning',
                        'High' => 'danger',
                        'Urgent' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('ticketStatus.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'New' => 'info',
                        'Open' => 'success',
                        'In Progress' => 'warning',
                        'Pending' => 'gray',
                        'Resolved' => 'primary',
                        'Closed' => 'gray',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('responsible.name')
                    ->label('Assigned To')
                    ->placeholder('Unassigned')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime(app(GeneralSettings::class)->datetime_format)
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\Filter::make('only_my_tickets')
                    ->label('Only My Tickets')
                    ->toggle()
                    ->query(function (Builder $query): Builder {
                        return $query->where('owner_id', auth()->id());
                    }),

                Tables\Filters\Filter::make('my_assigned_tickets')
                    ->label('My Assigned Tickets')
                    ->toggle()
                    ->visible(fn (): bool => auth()->user()?->hasRole('Staff Unit') ?? false)
                    ->query(function (Builder $query): Builder {
                        return $query->where('responsible_id', auth()->id());
                    }),

                Tables\Filters\Filter::make('unassigned_tickets')
                    ->label('Unassigned Tickets')
                    ->toggle()
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole([
                        'Super Admin',
                        'Admin Unit',
                    ]) ?? false)
                    ->query(function (Builder $query): Builder {
                        return $query->whereNull('responsible_id');
                    }),

                Tables\Filters\Filter::make('urgent_tickets')
                    ->label('Urgent Tickets')
                    ->toggle()
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('priority', function (Builder $query) {
                            $query->where('name', 'Urgent');
                        });
                    }),

                Tables\Filters\SelectFilter::make('owner_id')
                    ->label('Owner')
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole([
                        'Super Admin',
                        'Admin Unit',
                    ]) ?? false)
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('unit_id')
                    ->label('Unit')
                    ->visible(fn (): bool => auth()->user()?->hasRole('Super Admin') ?? false)
                    ->relationship('unit', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('priority_id')
                    ->label('Priority')
                    ->relationship('priority', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('ticket_statuses_id')
                    ->label('Status')
                    ->relationship('ticketStatus', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('responsible_id')
                    ->label('Assigned Staff')
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole([
                        'Super Admin',
                        'Admin Unit',
                    ]) ?? false)
                    ->relationship('responsible', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->hasAnyRole([
                        'Super Admin',
                        'Admin Unit',
                        'Staff Unit',
                    ]) ?? false),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->hasRole('Super Admin') ?? false),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->hasRole('Super Admin') ?? false),

                Tables\Actions\ForceDeleteBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->hasRole('Super Admin') ?? false),

                Tables\Actions\RestoreBulkAction::make()
                    ->visible(fn (): bool => auth()->user()?->hasRole('Super Admin') ?? false),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'view' => Pages\ViewTicket::route('/{record}'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->with([
                'owner',
                'unit',
                'category',
                'priority',
                'ticketStatus',
                'responsible',
            ]);

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (! static::userCanAccessTickets()) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasAnyRole(['Super Admin', 'super_admin', 'Admin', 'admin'])) {
            return $query;
        }

        if ($user->hasRole('Admin Unit')) {
            return $query->where('unit_id', $user->unit_id);
        }

        if ($user->hasRole('Staff Unit')) {
            return $query->where('responsible_id', $user->id);
        }

        return $query->where('owner_id', $user->id);
    }
}
