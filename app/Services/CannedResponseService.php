<?php

namespace App\Services;

use App\Models\CannedResponse;
use App\Models\User;
use App\Repositories\Contracts\CannedResponseRepositoryInterface;

class CannedResponseService
{
    public function __construct(private CannedResponseRepositoryInterface $responses) {}

    public function create(User $creator, array $attributes): CannedResponse
    {
        return $this->responses->create([
            'company_id' => $creator->company_id,
            'created_by_id' => $creator->id,
            ...$attributes,
        ]);
    }

    public function activeForUser(User $user)
    {
        return $this->responses->activeForUser($user);
    }
}
