<?php

namespace App\Policies;

use App\Models\SupportNotification;
use App\Models\User;

class SupportNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('notifications.view');
    }

    public function view(User $user, SupportNotification $notification): bool
    {
        return $user->can('notifications.view')
            && $notification->recipient_id === $user->id
            && ($notification->company_id === null || $notification->company_id === $user->company_id);
    }

    public function markRead(User $user, SupportNotification $notification): bool
    {
        return $user->can('notifications.mark_read') && $this->view($user, $notification);
    }

    public function delete(User $user, SupportNotification $notification): bool
    {
        return $user->can('notifications.delete') && $this->view($user, $notification);
    }
}
