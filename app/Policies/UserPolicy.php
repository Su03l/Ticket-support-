<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('users.view');
    }

    public function view(User $user, User $managedUser): bool
    {
        if ($this->canAccessProfile($user, $managedUser)) {
            return true;
        }

        if (! $user->can('users.view')) {
            return false;
        }

        return $user->company_id === null || $user->company_id === $managedUser->company_id;
    }

    public function create(User $user): bool
    {
        return $user->can('users.create');
    }

    public function update(User $user, User $managedUser): bool
    {
        if ($this->canAccessProfile($user, $managedUser)) {
            return true;
        }

        if (! $user->can('users.update') || ! $this->view($user, $managedUser)) {
            return false;
        }

        return $managedUser->user_type !== UserType::SuperAdmin || $user->company_id === null;
    }

    public function delete(User $user, User $managedUser): bool
    {
        return $user->can('users.delete')
            && $this->update($user, $managedUser)
            && $user->id !== $managedUser->id;
    }

    public function suspend(User $user, User $managedUser): bool
    {
        return $user->can('users.suspend')
            && $this->update($user, $managedUser)
            && $user->id !== $managedUser->id;
    }

    public function restore(User $user, User $managedUser): bool
    {
        return $user->can('users.restore') && $this->update($user, $managedUser);
    }

    public function updateAvatar(User $user, User $managedUser): bool
    {
        return $this->update($user, $managedUser);
    }

    public function updatePreferences(User $user, User $managedUser): bool
    {
        return $user->id === $managedUser->id || $user->user_type === UserType::SuperAdmin;
    }

    private function canAccessProfile(User $user, User $managedUser): bool
    {
        if ($user->id === $managedUser->id) {
            return true;
        }

        if ($user->user_type === UserType::SuperAdmin) {
            return true;
        }

        return $user->user_type === UserType::CompanyAdmin
            && $user->company_id !== null
            && $user->company_id === $managedUser->company_id
            && $managedUser->user_type !== UserType::SuperAdmin;
    }
}
