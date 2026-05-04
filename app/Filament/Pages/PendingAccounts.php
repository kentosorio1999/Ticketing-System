<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PendingAccounts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Pending Accounts';

    protected static ?string $title = 'Pending Accounts';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.pending-accounts';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->hasAnyRole([
            'Super Admin',
            'Admin Unit',
        ]);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getPendingAccountsQuery())
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->disk('public')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit')
                    ->placeholder('No unit')
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (): string => 'Pending Approval')
                    ->color('warning'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('unit_id')
                    ->label('Unit')
                    ->relationship('unit', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve account request')
                    ->modalDescription('This will approve the account and move it to Users.')
                    ->action(function (User $record): void {
                        $record->update([
                            'is_active' => true,
                        ]);

                        Notification::make()
                            ->title('Account Approved')
                            ->body('Your account has been approved. You can now access the system.')
                            ->success()
                            ->sendToDatabase($record);

                        Notification::make()
                            ->title('Account Approved')
                            ->body($record->name.' has been moved to Users.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('decline')
                    ->label('Decline')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Decline account request')
                    ->modalDescription('This will remove the pending account. The user will not be able to access the system.')
                    ->action(function (User $record): void {
                        $name = $record->name;

                        $record->delete();

                        Notification::make()
                            ->title('Account Declined')
                            ->body($name.' has been removed from pending accounts.')
                            ->danger()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No pending accounts')
            ->emptyStateDescription('All accounts are already reviewed.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->defaultSort('created_at', 'desc');
    }

    protected function getPendingAccountsQuery(): Builder
    {
        $query = User::query()
            ->with(['unit', 'roles'])
            ->where('is_active', false);

        $user = auth()->user();

        if ($user?->hasRole('Admin Unit')) {
            $query->where('unit_id', $user->unit_id);
        }

        return $query;
    }
}
