<?php

namespace App\Repositories\Contracts;

use App\Models\Company;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Collection;

interface SubscriptionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Subscription;

    public function find(int $id): ?Subscription;

    public function activeForCompany(Company $company): ?Subscription;

    /**
     * @return Collection<int, Subscription>
     */
    public function forCompany(Company $company): Collection;
}
