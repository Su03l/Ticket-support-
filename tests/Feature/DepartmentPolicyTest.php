<?php

use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;

test('super admin can manage every department', function () {
    $department = Department::factory()->create();
    $superAdmin = User::factory()->create(['user_type' => UserType::SuperAdmin]);

    expect($superAdmin->can('viewAny', Department::class))->toBeTrue()
        ->and($superAdmin->can('view', $department))->toBeTrue()
        ->and($superAdmin->can('create', Department::class))->toBeTrue()
        ->and($superAdmin->can('update', $department))->toBeTrue()
        ->and($superAdmin->can('assignMembers', $department))->toBeTrue()
        ->and($superAdmin->can('delete', $department))->toBeTrue();
});

test('company admin can manage only company departments', function () {
    $company = Company::factory()->create();
    $department = Department::factory()->for($company)->create();
    $otherDepartment = Department::factory()->create();
    $companyAdmin = User::factory()->create([
        'company_id' => $company->id,
        'user_type' => UserType::CompanyAdmin,
    ]);

    expect($companyAdmin->can('view', $department))->toBeTrue()
        ->and($companyAdmin->can('update', $department))->toBeTrue()
        ->and($companyAdmin->can('assignMembers', $department))->toBeTrue()
        ->and($companyAdmin->can('delete', $department))->toBeTrue()
        ->and($companyAdmin->can('view', $otherDepartment))->toBeFalse()
        ->and($companyAdmin->can('update', $otherDepartment))->toBeFalse();
});

test('department managers deputies and members stay inside visibility boundaries', function () {
    $company = Company::factory()->create();
    $department = Department::factory()->for($company)->create();
    $otherDepartment = Department::factory()->for($company)->create();
    $manager = User::factory()->create([
        'company_id' => $company->id,
        'user_type' => UserType::DepartmentManager,
    ]);
    $deputy = User::factory()->create([
        'company_id' => $company->id,
        'user_type' => UserType::DepartmentDeputy,
    ]);
    $member = User::factory()->create([
        'company_id' => $company->id,
        'department_id' => $department->id,
        'user_type' => UserType::SupportAgent,
    ]);

    $department->update([
        'manager_id' => $manager->id,
        'deputy_id' => $deputy->id,
    ]);

    expect($manager->can('view', $department))->toBeTrue()
        ->and($manager->can('view', $otherDepartment))->toBeFalse()
        ->and($manager->can('update', $department))->toBeTrue()
        ->and($manager->can('update', $otherDepartment))->toBeFalse()
        ->and($manager->can('assignMembers', $department))->toBeTrue()
        ->and($deputy->can('view', $department))->toBeTrue()
        ->and($deputy->can('assignMembers', $department))->toBeTrue()
        ->and($deputy->can('update', $department))->toBeFalse()
        ->and($member->can('view', $department))->toBeTrue()
        ->and($member->can('view', $otherDepartment))->toBeFalse()
        ->and($member->can('update', $department))->toBeFalse();
});

test('customers cannot view or manage departments', function () {
    $department = Department::factory()->create();
    $customer = User::factory()->create([
        'company_id' => $department->company_id,
        'user_type' => UserType::Customer,
    ]);

    expect($customer->can('viewAny', Department::class))->toBeFalse()
        ->and($customer->can('view', $department))->toBeFalse()
        ->and($customer->can('create', Department::class))->toBeFalse()
        ->and($customer->can('update', $department))->toBeFalse()
        ->and($customer->can('assignMembers', $department))->toBeFalse()
        ->and($customer->can('delete', $department))->toBeFalse();
});
