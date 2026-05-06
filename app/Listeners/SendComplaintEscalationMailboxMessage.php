<?php

namespace App\Listeners;

use App\Enums\MailboxMessageType;
use App\Enums\UserType;
use App\Events\ComplaintEscalated;
use App\Models\User;
use App\Services\MailboxService;

class SendComplaintEscalationMailboxMessage
{
    public function __construct(
        private MailboxService $mailbox,
    ) {}

    public function handle(ComplaintEscalated $event): void
    {
        $recipients = User::query()
            ->where('company_id', $event->complaint->company_id)
            ->where('user_type', UserType::CompanyAdmin)
            ->get();

        foreach ($recipients as $recipient) {
            $this->mailbox->send(
                recipient: $recipient,
                subject: "Complaint {$event->complaint->complaint_number} escalated",
                body: $event->reason ?: "Complaint {$event->complaint->complaint_number} has been escalated.",
                sender: $event->actor,
                type: MailboxMessageType::Escalation,
                relatedType: 'complaint',
                relatedId: $event->complaint->id,
                companyId: $event->complaint->company_id,
            );
        }
    }
}
