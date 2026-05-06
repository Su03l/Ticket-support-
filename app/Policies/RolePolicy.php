<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        if (! $user->can('roles.update')) {
            return false;
        }

        if ($role->name === 'super_admin') {
            return $user->company_id === null;
        }

        return true;
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->can('roles.delete') && ! in_array($role->name, $this->protectedRoles(), true);
    }

    /**
     * @return array<int, string>
     */
    private function protectedRoles(): array
    {
        return ['super_admin', 'company_admin', 'department_manager', 'department_deputy', 'support_agent', 'customer'];
    }
}
