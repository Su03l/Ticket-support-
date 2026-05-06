<?php

namespace App\Repositories;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CompanyRepository implements CompanyRepositoryInterface
{
    public function create(array $attributes): Company
    {
        return Company::create($attributes);
    }

    public function update(Company $company, array $attributes): Company
    {
        $company->update($attributes);

        return $company->refresh();
    }

    public function find(int $id): ?Company
    {
        return Company::query()->find($id);
    }

    public function findBySlug(string $slug): ?Company
    {
        return Company::query()->where('slug', $slug)->first();
    }

    public function active(): Collection
    {
        return Company::query()
            ->where('status', CompanyStatus::Active)
            ->orderBy('name')
            ->get();
    }
}
