<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Services\NotificationService;

class SendTicketCreatedNotification
{
    public function __construct(
        private NotificationService $notifications,
    ) {}

    public function handle(TicketCreated $event): void
    {
        $this->notifications->notify(
            recipient: $event->ticket->customer,
            type: 'ticket.created',
            title: 'Ticket created',
            body: "Ticket {$event->ticket->ticket_number} has been created.",
            link: route('tickets.show', $event->ticket),
            company: $event->ticket->company,
        );
    }
}
