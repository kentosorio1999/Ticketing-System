<?php

namespace App\Observers;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketCreated;
use App\Notifications\TicketDeleted;
use App\Notifications\TicketRestored;
use App\Notifications\TicketStatusUpdated;
use App\Settings\AccountSettings;
use Illuminate\Support\Collection;

class TicketObserver
{
    /**
     * Handle the Ticket "created" event.
     */
    public function created(Ticket $ticket): void
    {
        $staffUsers = new Collection([]);

        $accountSettings = app(AccountSettings::class);

        $usersQuery = User::whereNotNull('email')
            ->when($accountSettings->user_email_verification, function ($query) {
                $query->whereNotNull('email_verified_at');
            });

        $usersQuery->clone()->role('Staff Unit')
            ->where('unit_id', $ticket->unit_id)
            ->get()
            ->each(function ($user) use (&$staffUsers) {
                $staffUsers->put($user->id, $user);
            });

        $usersQuery->clone()->role('Global Staff')
            ->get()
            ->each(function ($user) use (&$staffUsers) {
                $staffUsers->put($user->id, $user);
            });

        $authUser = auth()->user();

        if ($authUser && $staffUsers->has($authUser->id)) {
            $staffUsers->pull($authUser->id);
        }

        $staffUsers->each(fn ($user) => $user->notify(new TicketCreated($ticket)));
    }

    /**
     * Handle the Ticket "updated" event.
     */
    public function updated(Ticket $ticket): void
    {
        if (
            array_key_exists('ticket_statuses_id', $ticket->getDirty())
            && (
                (
                    array_key_exists('ticket_statuses_id', $ticket->getOriginal())
                    && $ticket->getDirty()['ticket_statuses_id'] != $ticket->getOriginal()['ticket_statuses_id']
                )
                || ! array_key_exists('ticket_statuses_id', $ticket->getOriginal())
            )
        ) {
            $authUser = auth()->user();
            $subscribers = $ticket->getSubscribers();

            if ($subscribers->has($authUser->id)) {
                $subscribers->pull($authUser->id);
            }

            $subscribers->each(fn ($subscriber) => $subscriber->notify(new TicketStatusUpdated($ticket)));
        }
    }

    /**
     * Handle the Ticket "deleted" event.
     */
    public function deleted(Ticket $ticket): void
    {
        $authUser = auth()->user();
        if ($ticket->owner->id != $authUser->id) {
            $ticket->owner->notify(new TicketDeleted($ticket));
        }
    }

    /**
     * Handle the Ticket "restored" event.
     */
    public function restored(Ticket $ticket): void
    {
        $authUser = auth()->user();
        if ($ticket->owner->id != $authUser->id) {
            $ticket->owner->notify(new TicketRestored($ticket));
        }
    }

    /**
     * Handle the Ticket "force deleted" event.
     */
    public function forceDeleted(Ticket $ticket): void
    {
        //
    }
}
