<?php

namespace App\Listeners;

use App\Enums\MailboxMessageType;
use App\Events\InquiryAssigned;
use App\Services\MailboxService;

class SendInquiryAssignedMailboxMessage
{
    public function __construct(private MailboxService $mailbox) {}

    public function handle(InquiryAssigned $event): void
    {
        $this->mailbox->send(
            recipient: $event->assignee,
            subject: "Inquiry {$event->inquiry->inquiry_number} assigned",
            body: "You have been assigned inquiry {$event->inquiry->inquiry_number}: {$event->inquiry->subject}",
            sender: $event->actor,
            type: MailboxMessageType::Assignment,
            relatedType: 'inquiry',
            relatedId: $event->inquiry->id,
            companyId: $event->inquiry->company_id,
        );
    }
}
