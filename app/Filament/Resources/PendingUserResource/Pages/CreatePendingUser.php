<?php

namespace App\Filament\Resources\PendingUserResource\Pages;

use App\Filament\Resources\PendingUserResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePendingUser extends CreateRecord
{
    protected static string $resource = PendingUserResource::class;
}
