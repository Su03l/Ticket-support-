<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

interface RoleRepositoryInterface
{
    public function all(): Collection;

    public function find(int $id): ?Role;

    /**
     * @param  array<int, string>  $permissions
     */
    public function create(string $name, array $permissions = []): Role;

    /**
     * @param  array<int, string>  $permissions
     */
    public function syncPermissions(Role $role, array $permissions): Role;
}
