<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class RoleManagementService
{
    public function __construct(
        private RoleRepositoryInterface $roles,
    ) {}

    public function all(): Collection
    {
        return $this->roles->all();
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function create(User $actor, string $name, array $permissions = []): Role
    {
        $role = $this->roles->create($this->roleNameFor($actor, $name), $permissions);

        activity()->performedOn($role)->causedBy($actor)->event('role.created')->log('Role created');

        return $role;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    public function syncPermissions(User $actor, Role $role, array $permissions): Role
    {
        $updated = $this->roles->syncPermissions($role, $permissions);

        activity()->performedOn($updated)->causedBy($actor)->event('role.permissions_updated')->log('Role permissions updated');

        return $updated;
    }

    public function delete(User $actor, Role $role): void
    {
        $role->delete();

        activity()->causedBy($actor)->event('role.deleted')->log('Role deleted');
    }

    public function isProtected(Role $role): bool
    {
        return in_array($role->name, ['super_admin', 'company_admin', 'department_manager', 'department_deputy', 'support_agent', 'customer'], true);
    }

    private function roleNameFor(User $actor, string $name): string
    {
        $normalized = str($name)->lower()->replaceMatches('/[^a-z0-9_]+/', '_')->trim('_')->toString();

        if ($actor->company_id === null) {
            return $normalized;
        }

        return "company_{$actor->company_id}_{$normalized}";
    }
}
