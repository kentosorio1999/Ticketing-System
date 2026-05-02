<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\User;
use Filament\Actions;
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
        $ticket = $this->record;

        // Ticket owner/user receives notification when their ticket is updated
        if ($ticket->owner) {
            Notification::make()
                ->title('Ticket Status Updated')
                ->body('Your ticket status has been updated to: ' . $ticket->ticketStatus?->name)
                ->info()
                ->url(TicketResource::getUrl('view', ['record' => $ticket]))
                ->sendToDatabase($ticket->owner);
        }

        // Assigned staff receives notification
        if ($ticket->responsible) {
            Notification::make()
                ->title('Assigned Ticket Updated')
                ->body('A ticket assigned to you has been updated.')
                ->info()
                ->url(TicketResource::getUrl('view', ['record' => $ticket]))
                ->sendToDatabase($ticket->responsible);
        }

        // Super Admin receives all ticket updates
        $superAdmins = User::role('Super Admin')->get();

        foreach ($superAdmins as $superAdmin) {
            Notification::make()
                ->title('Ticket Updated')
                ->body('A ticket has been updated: ' . $ticket->title)
                ->info()
                ->url(TicketResource::getUrl('view', ['record' => $ticket]))
                ->sendToDatabase($superAdmin);
        }

        // Admin Unit receives updates only from their own unit
        $adminUnits = User::role('Admin Unit')
            ->where('unit_id', $ticket->unit_id)
            ->get();

        foreach ($adminUnits as $adminUnit) {
            Notification::make()
                ->title('Unit Ticket Updated')
                ->body('A ticket in your unit has been updated: ' . $ticket->title)
                ->info()
                ->url(TicketResource::getUrl('view', ['record' => $ticket]))
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