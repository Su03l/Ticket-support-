<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('activity_logs.view') && $user->user_type !== UserType::Customer;
    }

    public function view(User $user, Activity $activity): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($user->company_id === null) {
            return true;
        }

        $subject = $activity->subject;

        return $subject !== null && $subject->getAttribute('company_id') === $user->company_id;
    }
}
