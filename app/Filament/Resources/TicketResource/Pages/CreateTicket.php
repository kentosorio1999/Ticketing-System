<?php

namespace App\Filament\Resources\TicketResource\Pages;

use App\Filament\Resources\TicketResource;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    public function getTitle(): string
    {
        return __('Open Ticket');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['owner_id'] = auth()->id();
        $data['ticket_statuses_id'] = 1;

        return $data;
    }

    protected function afterCreate(): void
    {
        $ticketUrl = TicketResource::getUrl('index');

        // Super Admin receives all new ticket notifications
        $superAdmins = User::role('Super Admin')->get();

        foreach ($superAdmins as $superAdmin) {
            Notification::make()
                ->title('New Ticket Created #' . $this->record->id)
                ->body('A new ticket has been created. Click View Tickets below.')
                ->success()
                ->actions([
                    Action::make('view_tickets')
                        ->label('View Tickets')
                        ->button()
                        ->url($ticketUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($superAdmin);
        }

        // Admin Unit receives only tickets from their own unit
        $adminUnits = User::role('Admin Unit')
            ->where('unit_id', $this->record->unit_id)
            ->get();

        foreach ($adminUnits as $adminUnit) {
            Notification::make()
                ->title('New Unit Ticket Created #' . $this->record->id)
                ->body('A new ticket has been created in your unit. Click View Tickets below.')
                ->success()
                ->actions([
                    Action::make('view_tickets')
                        ->label('View Tickets')
                        ->button()
                        ->url($ticketUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($adminUnit);
        }
    }
}