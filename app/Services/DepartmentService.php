<?php

namespace App\Services;

use App\Enums\DepartmentStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

class DepartmentService
{
    public function __construct(
        private DepartmentRepositoryInterface $departments,
    ) {}

    /**
     * @param  array{name: string, slug?: string, description?: string|null, manager_id?: int|null, deputy_id?: int|null, status?: DepartmentStatus|string}  $attributes
     */
    public function createDepartment(Company $company, array $attributes): Department
    {
        $attributes['company_id'] = $company->id;
        $attributes['slug'] = $this->uniqueSlug($company, $attributes['slug'] ?? $attributes['name']);
        $attributes['status'] = $attributes['status'] ?? DepartmentStatus::Active;

        return $this->departments->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateDepartment(Department $department, array $attributes): Department
    {
        if (array_key_exists('slug', $attributes)) {
            $attributes['slug'] = $this->uniqueSlug($department->company, $attributes['slug'], $department);
        }

        return $this->departments->update($department, $attributes);
    }

    public function assignManager(Department $department, User $manager): Department
    {
        $this->ensureSameCompany($department, $manager);

        return $this->departments->update($department, [
            'manager_id' => $manager->id,
        ]);
    }

    public function assignDeputy(Department $department, User $deputy): Department
    {
        $this->ensureSameCompany($department, $deputy);

        return $this->departments->update($department, [
            'deputy_id' => $deputy->id,
        ]);
    }

    public function assignMember(Department $department, User $member): User
    {
        $this->ensureSameCompany($department, $member);

        $member->forceFill([
            'department_id' => $department->id,
        ])->save();

        return $member->refresh();
    }

    public function transferMember(Department $fromDepartment, Department $toDepartment, User $member): User
    {
        if ($fromDepartment->company_id !== $toDepartment->company_id) {
            throw new InvalidArgumentException('Department transfers must stay inside the same company.');
        }

        if ($member->company_id !== $fromDepartment->company_id || $member->department_id !== $fromDepartment->id) {
            throw new InvalidArgumentException('The user is not assigned to the source department.');
        }

        return $this->assignMember($toDepartment, $member);
    }

    public function deactivate(Department $department): Department
    {
        return $this->departments->update($department, [
            'status' => DepartmentStatus::Inactive,
        ]);
    }

    public function archive(Department $department): Department
    {
        return $this->departments->update($department, [
            'status' => DepartmentStatus::Archived,
        ]);
    }

    public function activate(Department $department): Department
    {
        return $this->departments->update($department, [
            'status' => DepartmentStatus::Active,
        ]);
    }

    private function ensureSameCompany(Department $department, User $user): void
    {
        if ($user->company_id !== $department->company_id) {
            throw new InvalidArgumentException('Department users must belong to the same company.');
        }
    }

    private function uniqueSlug(Company $company, string $value, ?Department $ignoreDepartment = null): string
    {
        $baseSlug = Str::slug($value) ?: 'department';
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($company, $slug, $ignoreDepartment)) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(Company $company, string $slug, ?Department $ignoreDepartment = null): bool
    {
        $department = $this->departments->findBySlugForCompany($company, $slug);

        if ($department === null) {
            return false;
        }

        return $ignoreDepartment === null || $department->id !== $ignoreDepartment->id;
    }
}
