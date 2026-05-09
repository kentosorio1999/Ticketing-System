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
use Spatie\Permission\Models\Role;

class PendingAccounts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Pending Accounts';

    protected static ?string $title = 'Pending Accounts';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'pending-accounts';

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

    public static function getNavigationBadge(): ?string
    {
        $count = User::query()
            ->where('is_active', false)
            ->whereDoesntHave('roles', function (Builder $query) {
                $query->whereIn('name', [
                    'Super Admin',
                    'super_admin',
                    'Admin',
                    'admin',
                    'Admin Unit',
                    'Staff Unit',
                ]);
            })
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
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
                    ->placeholder('Not Assigned')
                    ->sortable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->getStateUsing(function (User $record): string {
                        return $record->roles->pluck('name')->first() ?? 'User';
                    })
                    ->color('gray'),

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
                    ->modalDescription(fn (User $record): string => 'Are you sure you want to approve ' . $record->name . '?')
                    ->modalSubmitActionLabel('Yes, approve')
                    ->action(function (User $record): void {
                        $record->update([
                            'is_active' => true,
                        ]);

                        if (! $record->roles()->exists()) {
                            $role = Role::where('name', 'User')->first();

                            if ($role) {
                                $record->assignRole($role);
                            }
                        }

                        Notification::make()
                            ->title('Account Approved')
                            ->body('Your account has been approved. You can now access the system.')
                            ->success()
                            ->sendToDatabase($record);

                        Notification::make()
                            ->title('Account Approved')
                            ->body($record->name . ' has been moved to Users.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete pending account forever?')
                    ->modalDescription(fn (User $record): string => 'This will permanently delete ' . $record->name . '. The user can register again using the same email.')
                    ->modalSubmitActionLabel('Yes, delete forever')
                    ->action(function (User $record): void {
                        $name = $record->name;

                        // Permanent delete, not soft delete.
                        $record->forceDelete();

                        Notification::make()
                            ->title('Pending Account Deleted')
                            ->body($name . ' has been permanently deleted.')
                            ->success()
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
            ->where('is_active', false)
            ->whereDoesntHave('roles', function (Builder $query) {
                $query->whereIn('name', [
                    'Super Admin',
                    'super_admin',
                    'Admin',
                    'admin',
                    'Admin Unit',
                    'Staff Unit',
                ]);
            });

        $user = auth()->user();

        if ($user?->hasRole('Admin Unit')) {
            $query->where('unit_id', $user->unit_id);
        }

        return $query;
    }
}