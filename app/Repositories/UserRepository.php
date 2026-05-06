<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function paginatedForManager(User $manager, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->with(['company:id,name', 'department:id,name', 'roles:id,name'])
            ->when($manager->company_id !== null, fn ($query) => $query->where('company_id', $manager->company_id))
            ->when(($filters['search'] ?? null), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(($filters['status'] ?? null), fn ($query, string $status) => $query->where('status', $status))
            ->when(($filters['user_type'] ?? null), fn ($query, string $userType) => $query->where('user_type', $userType))
            ->latest()
            ->paginate($perPage);
    }

    public function activeByEmail(string $email): ?User
    {
        return User::query()
            ->where('email', $email)
            ->first();
    }

    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    public function update(User $user, array $attributes): User
    {
        $user->forceFill($attributes)->save();

        return $user->refresh();
    }
}
