<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->user_type === UserType::SuperAdmin
            || $user->user_type === UserType::CompanyAdmin;
    }

    public function view(User $user, Company $company): bool
    {
        return $user->user_type === UserType::SuperAdmin
            || ($user->user_type === UserType::CompanyAdmin && $user->company_id === $company->id);
    }

    public function create(User $user): bool
    {
        return $user->user_type === UserType::SuperAdmin;
    }

    public function update(User $user, Company $company): bool
    {
        return $this->view($user, $company);
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->user_type === UserType::SuperAdmin && $user->company_id !== $company->id;
    }

    public function restore(User $user, Company $company): bool
    {
        return $user->user_type === UserType::SuperAdmin;
    }

    public function forceDelete(User $user, Company $company): bool
    {
        return $user->user_type === UserType::SuperAdmin;
    }
}
