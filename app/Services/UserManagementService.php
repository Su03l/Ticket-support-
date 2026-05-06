<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class UserManagementService
{
    public function __construct(
        private UserRepositoryInterface $users,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listForManager(User $manager, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->users->paginatedForManager($manager, $filters, $perPage);
    }

    /**
     * @param  array{name: string, email: string, user_type: UserType|string, role_name?: string|null, department_id?: int|null, status?: UserStatus|string|null}  $attributes
     */
    public function createUser(User $actor, array $attributes): User
    {
        $userType = $attributes['user_type'] instanceof UserType ? $attributes['user_type'] : UserType::from($attributes['user_type']);
        $this->ensureCanManageType($actor, $userType);
        $this->ensureEmailAvailable($attributes['email']);

        $companyId = $actor->company_id === null ? ($attributes['company_id'] ?? null) : $actor->company_id;

        if ($userType !== UserType::SuperAdmin && $companyId === null) {
            throw new InvalidArgumentException('Company users must belong to a company.');
        }

        return DB::transaction(function () use ($actor, $attributes, $companyId, $userType): User {
            $user = $this->users->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => Hash::make(Str::password(32)),
                'company_id' => $userType === UserType::SuperAdmin ? null : $companyId,
                'department_id' => $attributes['department_id'] ?? null,
                'user_type' => $userType,
                'status' => $attributes['status'] ?? UserStatus::Active,
                'locale' => 'ar',
                'email_verified_at' => null,
            ]);

            $roleName = $attributes['role_name'] ?? $userType->value;
            $this->assignRole($actor, $user, $roleName);

            activity()->performedOn($user)->causedBy($actor)->event('user.created')->log('User created');

            return $user->refresh()->load('roles:id,name');
        });
    }

    public function assignRole(User $actor, User $managedUser, string $roleName): User
    {
        if ($actor->company_id !== null && $roleName === UserType::SuperAdmin->value) {
            throw new InvalidArgumentException('Company admins cannot assign the super admin role.');
        }

        $managedUser->syncRoles([$roleName]);

        activity()->performedOn($managedUser)->causedBy($actor)->event('user.role_assigned')->log('User role assigned');

        return $managedUser->refresh()->load('roles:id,name');
    }

    public function updateUser(User $actor, User $managedUser, array $attributes): User
    {
        if ($actor->company_id !== null && $actor->company_id !== $managedUser->company_id) {
            throw new InvalidArgumentException('Company admins cannot manage users outside their company.');
        }

        $updated = $this->users->update($managedUser, [
            'name' => $attributes['name'] ?? $managedUser->name,
            'department_id' => $attributes['department_id'] ?? $managedUser->department_id,
        ]);

        activity()->performedOn($updated)->causedBy($actor)->event('user.updated')->log('User updated');

        return $updated;
    }

    public function suspend(User $actor, User $managedUser): User
    {
        return $this->setStatus($actor, $managedUser, UserStatus::Suspended, 'user.suspended');
    }

    public function restore(User $actor, User $managedUser): User
    {
        return $this->setStatus($actor, $managedUser, UserStatus::Active, 'user.restored');
    }

    private function setStatus(User $actor, User $managedUser, UserStatus $status, string $event): User
    {
        $updated = $this->users->update($managedUser, ['status' => $status]);

        activity()->performedOn($updated)->causedBy($actor)->event($event)->log('User status updated');

        return $updated;
    }

    private function ensureCanManageType(User $actor, UserType $userType): void
    {
        if ($actor->company_id !== null && $userType === UserType::SuperAdmin) {
            throw new InvalidArgumentException('Company admins cannot create super admins.');
        }
    }

    private function ensureEmailAvailable(string $email): void
    {
        if ($this->users->activeByEmail($email) !== null) {
            throw new InvalidArgumentException('A user with this email already exists.');
        }
    }
}
