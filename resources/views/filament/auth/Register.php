<?php

namespace App\Filament\Pages\Auth;

use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as BaseRegister;

class Register extends BaseRegister
{
    protected function mutateFormDataBeforeRegister(array $data): array
    {
        // New registered users will be pending by default
        $data['is_active'] = false;

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Account Created Successfully')
            ->body('Your account has been created successfully and is waiting for Super Admin approval.');
    }
}