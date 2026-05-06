<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    public function create(array $attributes): Department
    {
        return Department::create($attributes);
    }

    public function update(Department $department, array $attributes): Department
    {
        $department->update($attributes);

        return $department->refresh();
    }

    public function find(int $id): ?Department
    {
        return Department::query()->find($id);
    }

    public function findForCompany(Company $company, int $id): ?Department
    {
        return Department::query()
            ->whereBelongsTo($company)
            ->whereKey($id)
            ->first();
    }

    public function findBySlugForCompany(Company $company, string $slug): ?Department
    {
        return Department::query()
            ->whereBelongsTo($company)
            ->where('slug', $slug)
            ->first();
    }

    public function forCompany(Company $company): Collection
    {
        return Department::query()
            ->whereBelongsTo($company)
            ->orderBy('name')
            ->get();
    }
}
