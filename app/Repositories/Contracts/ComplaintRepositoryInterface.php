<?php

namespace App\Repositories\Contracts;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ComplaintRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginatedForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Complaint;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Complaint $complaint, array $attributes): Complaint;

    public function findVisibleForUser(User $user, int $id): ?Complaint;
}
