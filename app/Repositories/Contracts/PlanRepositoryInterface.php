<?php

namespace App\Repositories\Contracts;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Collection;

interface PlanRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Plan;

    public function find(int $id): ?Plan;

    public function findBySlug(string $slug): ?Plan;

    /**
     * @return Collection<int, Plan>
     */
    public function active(): Collection;
}
