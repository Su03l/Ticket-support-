<?php

namespace App\Repositories\Contracts;

use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;

interface DepartmentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Department;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Department $department, array $attributes): Department;

    public function find(int $id): ?Department;

    public function findForCompany(Company $company, int $id): ?Department;

    public function findBySlugForCompany(Company $company, string $slug): ?Department;

    /**
     * @return Collection<int, Department>
     */
    public function forCompany(Company $company): Collection;
}
