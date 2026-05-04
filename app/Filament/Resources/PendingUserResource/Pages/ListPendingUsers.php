<?php

namespace App\Filament\Resources\PendingUserResource\Pages;

use App\Filament\Resources\PendingUserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPendingUsers extends ListRecords
{
    protected static string $resource = PendingUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
