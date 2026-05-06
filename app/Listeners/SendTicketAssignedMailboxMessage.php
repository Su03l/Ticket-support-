<?php

namespace App\Listeners;

use App\Enums\MailboxMessageType;
use App\Events\TicketAssigned;
use App\Services\MailboxService;

class SendTicketAssignedMailboxMessage
{
    public function __construct(
        private MailboxService $mailbox,
    ) {}

    public function handle(TicketAssigned $event): void
    {
        $this->mailbox->send(
            recipient: $event->assignee,
            subject: "Ticket {$event->ticket->ticket_number} assigned",
            body: "You have been assigned ticket {$event->ticket->ticket_number}: {$event->ticket->title}",
            sender: $event->actor,
            type: MailboxMessageType::Assignment,
            relatedType: 'ticket',
            relatedId: $event->ticket->id,
            companyId: $event->ticket->company_id,
        );
    }
}
