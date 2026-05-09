<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\RolesRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\TicketsRelationManager;
use App\Models\Unit;
use App\Models\User;
use App\Settings\GeneralSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('Users');
    }

    public static function getModelLabel(): string
    {
        return __('User');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Users');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('avatar_url')
                    ->label(__('Avatar'))
                    ->image()
                    ->avatar()
                    ->imageEditor()
                    ->disk('public')
                    ->directory('avatars')
                    ->visibility('public'),

                Forms\Components\Select::make('unit_id')
                    ->label(__('Unit'))
                    ->options(Unit::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('name')
                    ->translateLabel()
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->translateLabel()
                    ->email()
                    ->required()
                    ->maxLength(255),

                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->translateLabel()
                    ->native(false)
                    ->displayFormat(app(GeneralSettings::class)->datetime_format),

                Forms\Components\TextInput::make('password')
                    ->translateLabel()
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255),

                Forms\Components\TextInput::make('identity')
                    ->translateLabel()
                    ->maxLength(255),

                Forms\Components\TextInput::make('phone')
                    ->translateLabel()
                    ->tel()
                    ->maxLength(255),

                Forms\Components\Select::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->required()
                    ->helperText('Select the role of this user.'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Approved / Active')
                    ->helperText('Turn on if this account is approved and allowed to access the system.')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label(__('Avatar'))
                    ->disk('public')
                    ->circular(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit')
                    ->placeholder('Not Assigned')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->getStateUsing(function (User $record): string {
                        return $record->roles->pluck('name')->first() ?? 'User';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Super Admin' => 'danger',
                        'Admin Unit', 'Admin' => 'info',
                        'Staff Unit', 'Staff' => 'success',
                        'User' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('is_active')
                    ->label('Account Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Approved' : 'Pending Approval')
                    ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

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

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Account Status')
                    ->options([
                        true => 'Approved',
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),

                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->visible(function (User $record): bool {
                            return self::canManageAccount($record)
                                && $record->id !== auth()->id()
                                && ! $record->hasRole('Super Admin')
                                && $record->is_active;
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate Account')
                        ->modalDescription(fn (User $record): string => 'Are you sure you want to deactivate ' . $record->name . '?')
                        ->action(function (User $record): void {
                            $record->update([
                                'is_active' => false,
                            ]);

                            Notification::make()
                                ->title('Account Deactivated')
                                ->body('Your account has been deactivated. Please contact the administrator.')
                                ->danger()
                                ->sendToDatabase($record);

                            Notification::make()
                                ->title('Account Deactivated')
                                ->body($record->name . ' has been deactivated.')
                                ->danger()
                                ->send();
                        }),

                    Impersonate::make()
                        ->visible(function (User $record): bool {
                            return self::canManageAccount($record)
                                && $record->id !== auth()->id()
                                && ! $record->hasRole('Super Admin')
                                && $record->is_active;
                        })
                        ->redirectTo(route('filament.admin.pages.dashboard')),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),

                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false),
                ]),
            ])
            ->defaultSort('name');
    }

    private static function canManageAccount(User $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasRole('Admin Unit')) {
            return $record->unit_id === $user->unit_id;
        }

        return false;
    }

    public static function getRelations(): array
    {
        return [
            RolesRelationManager::class,
            TicketsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ])
            ->where('is_active', true);

        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->hasRole('Admin Unit')) {
            return $query->where('unit_id', $user->unit_id);
        }

        return $query;
    }
}