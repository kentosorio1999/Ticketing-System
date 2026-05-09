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
        // New registered users are inactive/pending by default.
        // If Super Admin creates a user and sets is_active manually, it will not be changed.
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

        /*
        |--------------------------------------------------------------------------
        | Notify Super Admins
        |--------------------------------------------------------------------------
        */
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

        /*
        |--------------------------------------------------------------------------
        | Notify Admins from the same unit
        |--------------------------------------------------------------------------
        | Use "Admin" here because your system role is Admin, not Admin Unit.
        */
        if ($user->unit_id) {
            $admins = User::role('Admin')
                ->where('unit_id', $user->unit_id)
                ->get();

            foreach ($admins as $admin) {
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
                    ->sendToDatabase($admin);
            }
        }
    }
}