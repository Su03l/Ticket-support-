<?php

namespace App\Repositories;

use App\Models\Plan;
use App\Repositories\Contracts\PlanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class PlanRepository implements PlanRepositoryInterface
{
    public function create(array $attributes): Plan
    {
        return Plan::create($attributes);
    }

    public function find(int $id): ?Plan
    {
        return Plan::query()->find($id);
    }

    public function findBySlug(string $slug): ?Plan
    {
        return Plan::query()->where('slug', $slug)->first();
    }

    public function active(): Collection
    {
        return Plan::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->orderBy('name')
            ->get();
    }
}
