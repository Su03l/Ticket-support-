<?php

namespace App\Listeners;

use App\Enums\UserType;
use App\Events\InquiryAnswered;
use App\Services\NotificationService;

class SendInquiryAnsweredNotification
{
    public function __construct(private NotificationService $notifications) {}

    public function handle(InquiryAnswered $event): void
    {
        if ($event->actor->user_type === UserType::Customer) {
            return;
        }

        $this->notifications->notify(
            recipient: $event->inquiry->customer,
            type: 'inquiry.answered',
            title: 'Inquiry answered',
            body: "Inquiry {$event->inquiry->inquiry_number} has a new answer.",
            link: route('inquiries.show', $event->inquiry),
            company: $event->inquiry->company,
        );
    }
}
