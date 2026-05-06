<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserInvitation;

class UserInvitationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.invite') || $user->can('users.view');
    }

    public function create(User $user): bool
    {
        return $user->can('users.invite') && $user->can('users.create');
    }

    public function resend(User $user, UserInvitation $invitation): bool
    {
        return $user->can('users.invite')
            && ($user->company_id === null || $user->company_id === $invitation->company_id)
            && $invitation->accepted_at === null;
    }
}
