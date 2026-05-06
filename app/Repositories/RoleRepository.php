<?php

namespace App\Repositories;

use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class RoleRepository implements RoleRepositoryInterface
{
    public function all(): Collection
    {
        return Role::query()
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?Role
    {
        return Role::query()->with('permissions:id,name')->find($id);
    }

    public function create(string $name, array $permissions = []): Role
    {
        $role = Role::findOrCreate($name);
        $role->syncPermissions($permissions);

        return $role->refresh()->load('permissions:id,name');
    }

    public function syncPermissions(Role $role, array $permissions): Role
    {
        $role->syncPermissions($permissions);

        return $role->refresh()->load('permissions:id,name');
    }
}
