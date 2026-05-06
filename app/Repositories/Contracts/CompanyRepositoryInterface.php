<?php

namespace App\Repositories\Contracts;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

interface CompanyRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Company;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Company $company, array $attributes): Company;

    public function find(int $id): ?Company;

    public function findBySlug(string $slug): ?Company;

    /**
     * @return Collection<int, Company>
     */
    public function active(): Collection;
}
