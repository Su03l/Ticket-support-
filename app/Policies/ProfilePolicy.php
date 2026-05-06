<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\User;

class ProfilePolicy
{
    public function view(User $user, User $profile): bool
    {
        return $this->update($user, $profile);
    }

    public function update(User $user, User $profile): bool
    {
        if ($user->id === $profile->id) {
            return true;
        }

        if ($user->user_type === UserType::SuperAdmin) {
            return true;
        }

        return $user->user_type === UserType::CompanyAdmin
            && $user->company_id !== null
            && $user->company_id === $profile->company_id
            && $profile->user_type !== UserType::SuperAdmin;
    }

    public function updateAvatar(User $user, User $profile): bool
    {
        return $this->update($user, $profile);
    }

    public function updatePreferences(User $user, User $profile): bool
    {
        return $user->id === $profile->id || $user->user_type === UserType::SuperAdmin;
    }
}
