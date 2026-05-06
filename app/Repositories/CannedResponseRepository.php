<?php

namespace App\Repositories;

use App\Models\CannedResponse;
use App\Models\User;
use App\Repositories\Contracts\CannedResponseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CannedResponseRepository implements CannedResponseRepositoryInterface
{
    public function activeForUser(User $user): Collection
    {
        return CannedResponse::query()
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('department_id')->orWhere('department_id', $user->department_id))
            ->orderBy('title')
            ->get();
    }

    public function create(array $attributes): CannedResponse
    {
        return CannedResponse::query()->create($attributes);
    }

    public function update(CannedResponse $response, array $attributes): CannedResponse
    {
        $response->forceFill($attributes)->save();
        return $response->refresh();
    }
}
