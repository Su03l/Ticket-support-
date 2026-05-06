<?php

namespace App\Services;

use App\Events\SupportNotificationCreated;
use App\Models\Company;
use App\Models\SupportNotification;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;

class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $notifications,
    ) {}

    /**
     * @param  array<string, mixed>|null  $data
     */
    public function notify(
        User $recipient,
        string $type,
        string $title,
        string $body,
        ?string $link = null,
        ?array $data = null,
        ?Company $company = null,
    ): SupportNotification {
        $notification = $this->notifications->create([
            'recipient_id' => $recipient->id,
            'company_id' => $company?->id ?? $recipient->company_id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link,
            'data' => $data,
        ]);

        broadcast(new SupportNotificationCreated($notification))->toOthers();

        return $notification;
    }

    public function markAsRead(User $recipient, SupportNotification $notification): SupportNotification
    {
        if ($notification->recipient_id !== $recipient->id) {
            abort(403);
        }

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return $notification->refresh();
    }

    public function markAllAsRead(User $recipient): int
    {
        return $this->notifications->markAllAsRead($recipient);
    }
}
