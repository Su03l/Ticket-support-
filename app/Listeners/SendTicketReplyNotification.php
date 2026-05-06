<?php

namespace App\Listeners;

use App\Enums\UserType;
use App\Events\TicketReplied;
use App\Services\NotificationService;

class SendTicketReplyNotification
{
    public function __construct(
        private NotificationService $notifications,
    ) {}

    public function handle(TicketReplied $event): void
    {
        if ($event->actor->user_type === UserType::Customer && $event->ticket->assignedAgent !== null) {
            $recipient = $event->ticket->assignedAgent;
        } elseif ($event->actor->id !== $event->ticket->customer_id) {
            $recipient = $event->ticket->customer;
        } else {
            return;
        }

        $this->notifications->notify(
            recipient: $recipient,
            type: 'ticket.reply',
            title: 'New ticket reply',
            body: "Ticket {$event->ticket->ticket_number} has a new reply.",
            link: route('tickets.show', $event->ticket),
            company: $event->ticket->company,
        );
    }
}
