<?php

namespace App\Listeners;

use App\Enums\UserType;
use App\Events\ComplaintCreated;
use App\Models\User;
use App\Services\NotificationService;

class SendComplaintCreatedNotification
{
    public function __construct(
        private NotificationService $notifications,
    ) {}

    public function handle(ComplaintCreated $event): void
    {
        $recipients = User::query()
            ->where('company_id', $event->complaint->company_id)
            ->whereIn('user_type', [UserType::CompanyAdmin, UserType::DepartmentManager])
            ->when($event->complaint->department_id !== null, function ($query): void {
                $query->where(function ($query): void {
                    $query->where('user_type', UserType::CompanyAdmin)
                        ->orWhereNotNull('department_id');
                });
            })
            ->get();

        foreach ($recipients as $recipient) {
            if ($recipient->user_type === UserType::DepartmentManager && $recipient->department_id !== $event->complaint->department_id) {
                continue;
            }

            $this->notifications->notify(
                recipient: $recipient,
                type: 'complaint.created',
                title: 'New complaint',
                body: "Complaint {$event->complaint->complaint_number} has been created.",
                link: route('complaints.show', $event->complaint),
                company: $event->complaint->company,
            );
        }
    }
}
