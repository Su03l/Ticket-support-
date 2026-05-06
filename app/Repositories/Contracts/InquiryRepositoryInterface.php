<?php

namespace App\Repositories\Contracts;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InquiryRepositoryInterface
{
    public function paginatedForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function create(array $attributes): Inquiry;
    public function update(Inquiry $inquiry, array $attributes): Inquiry;
    public function findVisibleForUser(User $user, int $id): ?Inquiry;
}
