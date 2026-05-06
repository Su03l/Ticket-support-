<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserInvitation;
use App\Repositories\Contracts\UserInvitationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserInvitationRepository implements UserInvitationRepositoryInterface
{
    public function create(array $attributes): UserInvitation
    {
        return UserInvitation::query()->create($attributes);
    }

    public function update(UserInvitation $invitation, array $attributes): UserInvitation
    {
        $invitation->forceFill($attributes)->save();

        return $invitation->refresh();
    }

    public function latestForManager(User $manager, int $perPage = 10): LengthAwarePaginator
    {
        return UserInvitation::query()
            ->with(['invitedBy:id,name,email', 'department:id,name'])
            ->when($manager->company_id !== null, fn ($query) => $query->where('company_id', $manager->company_id))
            ->latest()
            ->paginate($perPage);
    }
}
