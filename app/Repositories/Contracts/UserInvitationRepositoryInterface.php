<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserInvitationRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): UserInvitation;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(UserInvitation $invitation, array $attributes): UserInvitation;

    public function latestForManager(User $manager, int $perPage = 10): LengthAwarePaginator;
}
