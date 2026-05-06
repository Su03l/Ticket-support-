<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class PermissionManagementService
{
    public function grouped(?string $search = null): Collection
    {
        return Permission::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->groupBy(fn (Permission $permission): string => str($permission->name)->before('.')->toString());
    }

    /**
     * @return array<int, string>
     */
    public function names(?string $search = null): array
    {
        return $this->grouped($search)
            ->flatten()
            ->pluck('name')
            ->values()
            ->all();
    }
}
