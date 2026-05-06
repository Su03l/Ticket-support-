<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->user_type, [
            UserType::SuperAdmin,
            UserType::CompanyAdmin,
            UserType::DepartmentManager,
            UserType::DepartmentDeputy,
            UserType::SupportAgent,
        ], true);
    }

    public function view(User $user, Department $department): bool
    {
        if ($user->user_type === UserType::SuperAdmin) {
            return true;
        }

        if ($user->company_id !== $department->company_id) {
            return false;
        }

        return $user->user_type === UserType::CompanyAdmin
            || $user->department_id === $department->id
            || $department->manager_id === $user->id
            || $department->deputy_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->user_type === UserType::SuperAdmin
            || $user->user_type === UserType::CompanyAdmin;
    }

    public function update(User $user, Department $department): bool
    {
        if ($user->user_type === UserType::SuperAdmin) {
            return true;
        }

        if ($user->company_id !== $department->company_id) {
            return false;
        }

        return $user->user_type === UserType::CompanyAdmin
            || $department->manager_id === $user->id;
    }

    public function assignMembers(User $user, Department $department): bool
    {
        return $this->update($user, $department)
            || ($user->company_id === $department->company_id && $department->deputy_id === $user->id);
    }

    public function transferMembers(User $user, Department $department): bool
    {
        return $this->assignMembers($user, $department);
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->user_type === UserType::SuperAdmin
            || ($user->user_type === UserType::CompanyAdmin && $user->company_id === $department->company_id);
    }
}
