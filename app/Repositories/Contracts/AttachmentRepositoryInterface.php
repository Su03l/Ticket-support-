<?php

namespace App\Repositories\Contracts;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AttachmentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Attachment;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginatedForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
