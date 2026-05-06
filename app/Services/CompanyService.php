<?php

namespace App\Services;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\Plan;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use Illuminate\Support\Str;

class CompanyService
{
    public function __construct(
        private CompanyRepositoryInterface $companies,
    ) {}

    /**
     * @param  array{name: string, slug?: string, email?: string|null, phone?: string|null, website?: string|null, plan_id?: int|null, trial_ends_at?: mixed}  $attributes
     */
    public function createCompany(array $attributes): Company
    {
        $attributes['slug'] = $this->uniqueSlug($attributes['slug'] ?? $attributes['name']);
        $attributes['status'] = $attributes['status'] ?? CompanyStatus::Active;

        return $this->companies->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateCompany(Company $company, array $attributes): Company
    {
        if (array_key_exists('slug', $attributes)) {
            $attributes['slug'] = $this->uniqueSlug($attributes['slug'], $company);
        }

        return $this->companies->update($company, $attributes);
    }

    public function assignPlan(Company $company, ?Plan $plan): Company
    {
        return $this->companies->update($company, [
            'plan_id' => $plan?->id,
        ]);
    }

    public function suspend(Company $company): Company
    {
        return $this->companies->update($company, [
            'status' => CompanyStatus::Suspended,
            'suspended_at' => now(),
        ]);
    }

    public function activate(Company $company): Company
    {
        return $this->companies->update($company, [
            'status' => CompanyStatus::Active,
            'suspended_at' => null,
        ]);
    }

    private function uniqueSlug(string $value, ?Company $ignoreCompany = null): string
    {
        $baseSlug = Str::slug($value) ?: 'company';
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($slug, $ignoreCompany)) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?Company $ignoreCompany = null): bool
    {
        $company = $this->companies->findBySlug($slug);

        if ($company === null) {
            return false;
        }

        return $ignoreCompany === null || $company->id !== $ignoreCompany->id;
    }
}
