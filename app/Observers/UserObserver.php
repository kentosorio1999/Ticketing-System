<?php

namespace App\Observers;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class UserObserver
{
    public function creating(User $user): void
    {
        // New users are inactive by default unless explicitly set active.
        if ($user->is_active === null) {
            $user->is_active = false;
        }
    }

    public function created(User $user): void
    {
        // Notify only if the account is waiting for approval.
        if ($user->is_active) {
            return;
        }

        $userUrl = UserResource::getUrl('edit', ['record' => $user]);

        // Super Admin receives all new account approval requests.
        $superAdmins = User::role('Super Admin')->get();

        foreach ($superAdmins as $superAdmin) {
            Notification::make()
                ->title('New Account Approval Request')
                ->body($user->name . ' created an account and is waiting for approval.')
                ->warning()
                ->actions([
                    Action::make('review_user')
                        ->label('Review Account')
                        ->button()
                        ->url($userUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($superAdmin);
        }

        // Admin Unit receives only users from their own unit.
        if ($user->unit_id) {
            $adminUnits = User::role('Admin Unit')
                ->where('unit_id', $user->unit_id)
                ->get();

            foreach ($adminUnits as $adminUnit) {
                Notification::make()
                    ->title('New Unit Account Approval Request')
                    ->body($user->name . ' created an account in your unit and is waiting for approval.')
                    ->warning()
                    ->actions([
                        Action::make('review_user')
                            ->label('Review Account')
                            ->button()
                            ->url($userUrl)
                            ->markAsRead(),
                    ])
                    ->sendToDatabase($adminUnit);
            }
        }
    }
}