<?php

namespace App\Repositories;

use App\Enums\SubscriptionStatus;
use App\Models\Company;
use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function create(array $attributes): Subscription
    {
        return Subscription::create($attributes);
    }

    public function find(int $id): ?Subscription
    {
        return Subscription::query()->find($id);
    }

    public function activeForCompany(Company $company): ?Subscription
    {
        return Subscription::query()
            ->whereBelongsTo($company)
            ->where('status', SubscriptionStatus::Active)
            ->latest('starts_at')
            ->first();
    }

    public function forCompany(Company $company): Collection
    {
        return Subscription::query()
            ->whereBelongsTo($company)
            ->latest()
            ->get();
    }
}
