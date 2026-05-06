<?php

namespace App\Repositories\Contracts;

use App\Models\CannedResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface CannedResponseRepositoryInterface
{
    public function activeForUser(User $user): Collection;
    public function create(array $attributes): CannedResponse;
    public function update(CannedResponse $response, array $attributes): CannedResponse;
}
