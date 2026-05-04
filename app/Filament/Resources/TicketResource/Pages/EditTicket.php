<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\User;
use Filament\Actions;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function afterSave(): void
    {
        $ticket = $this->record->fresh([
            'owner',
            'responsible',
            'ticketStatus',
        ]);

        $ticketUrl = TicketResource::getUrl('view', ['record' => $ticket]);

        /*
         * Notify ticket owner/user only when status changed.
         */
        if ($ticket->wasChanged('ticket_statuses_id') && $ticket->owner) {
            Notification::make()
                ->title('Ticket Status Updated')
                ->body('Your ticket status has been updated to: ' . $ticket->ticketStatus?->name)
                ->info()
                ->actions([
                    NotificationAction::make('view_ticket')
                        ->label('View Ticket')
                        ->button()
                        ->url($ticketUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($ticket->owner);
        }

        /*
         * Notify staff only when ticket is assigned to them.
         */
        if ($ticket->wasChanged('responsible_id') && $ticket->responsible) {
            Notification::make()
                ->title('New Ticket Assigned')
                ->body('A ticket has been assigned to you: ' . $ticket->title)
                ->success()
                ->actions([
                    NotificationAction::make('view_ticket')
                        ->label('View Ticket')
                        ->button()
                        ->url($ticketUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($ticket->responsible);
        }

        /*
         * Notify assigned staff when ticket is updated,
         * but avoid duplicate notification if it was just assigned.
         */
        if (! $ticket->wasChanged('responsible_id') && $ticket->responsible) {
            Notification::make()
                ->title('Assigned Ticket Updated')
                ->body('A ticket assigned to you has been updated: ' . $ticket->title)
                ->info()
                ->actions([
                    NotificationAction::make('view_ticket')
                        ->label('View Ticket')
                        ->button()
                        ->url($ticketUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($ticket->responsible);
        }

        /*
         * Super Admin receives all ticket updates.
         */
        $superAdmins = User::role('Super Admin')->get();

        foreach ($superAdmins as $superAdmin) {
            if ($superAdmin->id === auth()->id()) {
                continue;
            }

            Notification::make()
                ->title('Ticket Updated')
                ->body('A ticket has been updated: ' . $ticket->title)
                ->info()
                ->actions([
                    NotificationAction::make('view_ticket')
                        ->label('View Ticket')
                        ->button()
                        ->url($ticketUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($superAdmin);
        }

        /*
         * Admin Unit receives updates only from their own unit.
         */
        $adminUnits = User::role('Admin Unit')
            ->where('unit_id', $ticket->unit_id)
            ->get();

        foreach ($adminUnits as $adminUnit) {
            if ($adminUnit->id === auth()->id()) {
                continue;
            }

            Notification::make()
                ->title('Unit Ticket Updated')
                ->body('A ticket in your unit has been updated: ' . $ticket->title)
                ->info()
                ->actions([
                    NotificationAction::make('view_ticket')
                        ->label('View Ticket')
                        ->button()
                        ->url($ticketUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($adminUnit);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}