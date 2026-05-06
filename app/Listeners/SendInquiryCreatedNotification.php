<?php

namespace App\Listeners;

use App\Enums\UserType;
use App\Events\InquiryCreated;
use App\Models\User;
use App\Services\NotificationService;

class SendInquiryCreatedNotification
{
    public function __construct(private NotificationService $notifications) {}

    public function handle(InquiryCreated $event): void
    {
        $recipients = User::query()
            ->where('company_id', $event->inquiry->company_id)
            ->whereIn('user_type', [UserType::CompanyAdmin, UserType::DepartmentManager])
            ->get();

        foreach ($recipients as $recipient) {
            if ($recipient->user_type === UserType::DepartmentManager && $event->inquiry->department_id !== $recipient->department_id) {
                continue;
            }

            $this->notifications->notify(
                recipient: $recipient,
                type: 'inquiry.created',
                title: 'New inquiry',
                body: "Inquiry {$event->inquiry->inquiry_number} has been created.",
                link: route('inquiries.show', $event->inquiry),
                company: $event->inquiry->company,
            );
        }
    }
}
