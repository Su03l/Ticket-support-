<?php

use App\Enums\DepartmentStatus;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use App\Services\DepartmentService;

test('department relationships expose company manager deputy and members', function () {
    $company = Company::factory()->create();
    $manager = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::DepartmentManager]);
    $deputy = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::DepartmentDeputy]);
    $department = Department::factory()->for($company)->create([
        'manager_id' => $manager->id,
        'deputy_id' => $deputy->id,
    ]);
    $member = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id]);

    expect($department->company->is($company))->toBeTrue()
        ->and($department->manager->is($manager))->toBeTrue()
        ->and($department->deputy->is($deputy))->toBeTrue()
        ->and($department->members->first()->is($member))->toBeTrue()
        ->and($company->departments->first()->is($department))->toBeTrue()
        ->and($member->department->is($department))->toBeTrue()
        ->and($manager->managedDepartment->is($department))->toBeTrue()
        ->and($deputy->deputyDepartment->is($department))->toBeTrue();
});

test('department service creates unique company scoped slugs and lifecycle changes', function () {
    $company = Company::factory()->create();
    $service = app(DepartmentService::class);

    $firstDepartment = $service->createDepartment($company, ['name' => 'Technical Support']);
    $secondDepartment = $service->createDepartment($company, ['name' => 'Technical Support']);

    expect($firstDepartment->slug)->toBe('technical-support')
        ->and($secondDepartment->slug)->toBe('technical-support-2')
        ->and($firstDepartment->status)->toBe(DepartmentStatus::Active);

    expect($service->deactivate($firstDepartment)->status)->toBe(DepartmentStatus::Inactive)
        ->and($service->archive($firstDepartment)->status)->toBe(DepartmentStatus::Archived)
        ->and($service->activate($firstDepartment)->status)->toBe(DepartmentStatus::Active);
});

test('department service assigns owners and members inside the same company', function () {
    $company = Company::factory()->create();
    $department = Department::factory()->for($company)->create();
    $manager = User::factory()->create(['company_id' => $company->id]);
    $deputy = User::factory()->create(['company_id' => $company->id]);
    $member = User::factory()->create(['company_id' => $company->id]);

    $service = app(DepartmentService::class);

    expect($service->assignManager($department, $manager)->manager_id)->toBe($manager->id)
        ->and($service->assignDeputy($department, $deputy)->deputy_id)->toBe($deputy->id)
        ->and($service->assignMember($department, $member)->department_id)->toBe($department->id);
});

test('department service blocks cross company assignment and transfer', function () {
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $otherCompanyUser = User::factory()->create(['company_id' => $otherDepartment->company_id]);

    $service = app(DepartmentService::class);

    expect(fn () => $service->assignMember($department, $otherCompanyUser))
        ->toThrow(InvalidArgumentException::class);

    $member = User::factory()->create([
        'company_id' => $department->company_id,
        'department_id' => $department->id,
    ]);

    expect(fn () => $service->transferMember($department, $otherDepartment, $member))
        ->toThrow(InvalidArgumentException::class);
});

test('department service transfers users between company departments', function () {
    $company = Company::factory()->create();
    $fromDepartment = Department::factory()->for($company)->create();
    $toDepartment = Department::factory()->for($company)->create();
    $member = User::factory()->create([
        'company_id' => $company->id,
        'department_id' => $fromDepartment->id,
    ]);

    $transferredMember = app(DepartmentService::class)->transferMember($fromDepartment, $toDepartment, $member);

    expect($transferredMember->department_id)->toBe($toDepartment->id);
});

test('department repository returns company scoped departments', function () {
    $company = Company::factory()->create();
    $department = Department::factory()->for($company)->create();
    $otherDepartment = Department::factory()->create();

    $repository = app(DepartmentRepositoryInterface::class);

    expect($repository->findForCompany($company, $department->id)?->is($department))->toBeTrue()
        ->and($repository->findForCompany($company, $otherDepartment->id))->toBeNull()
        ->and($repository->forCompany($company))->toHaveCount(1);
});
