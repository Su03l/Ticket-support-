<?php

namespace App\Listeners;

use App\Events\ComplaintStatusChanged;
use App\Services\NotificationService;

class SendComplaintStatusNotification
{
    public function __construct(
        private NotificationService $notifications,
    ) {}

    public function handle(ComplaintStatusChanged $event): void
    {
        if ($event->actor->id === $event->complaint->customer_id) {
            return;
        }

        $this->notifications->notify(
            recipient: $event->complaint->customer,
            type: 'complaint.status_changed',
            title: 'Complaint status updated',
            body: "Complaint {$event->complaint->complaint_number} is now {$event->newStatus->value}.",
            link: route('complaints.show', $event->complaint),
            company: $event->complaint->company,
        );
    }
}
