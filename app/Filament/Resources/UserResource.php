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

    public static function getNavigationGroup(): ?string
    {
        return __('Administration');
    }

    public static function getModelLabel(): string
    {
        return __('User');
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
                    ->options(Unit::query()->pluck('name', 'id'))
                    ->searchable(),

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

                Forms\Components\Toggle::make('is_active')
                    ->translateLabel()
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
                    ->translateLabel()
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->translateLabel()
                    ->searchable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->translateLabel(),

                Tables\Columns\IconColumn::make('is_active')
                    ->translateLabel()
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Impersonate::make()
                    ->redirectTo(route('filament.admin.pages.dashboard')),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ]);
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
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}