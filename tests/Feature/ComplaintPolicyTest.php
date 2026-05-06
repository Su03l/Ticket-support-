<?php

use App\Enums\UserType;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('customer can view own complaint only', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $otherCustomer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $customer->assignRole(UserType::Customer->value);
    $otherCustomer->assignRole(UserType::Customer->value);
    $complaint = Complaint::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create();

    expect($customer->can('view', $complaint))->toBeTrue()
        ->and($otherCustomer->can('view', $complaint))->toBeFalse();
});

test('company admin can view company complaint but not another tenant complaint', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::CompanyAdmin]);
    $admin->assignRole(UserType::CompanyAdmin->value);
    $companyComplaint = Complaint::factory()->create(['company_id' => $company->id]);
    $otherComplaint = Complaint::factory()->create();

    expect($admin->can('view', $companyComplaint))->toBeTrue()
        ->and($admin->can('view', $otherComplaint))->toBeFalse();
});

test('department manager can view and assign department complaints only', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $otherDepartment = Department::factory()->create(['company_id' => $company->id]);
    $manager = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::DepartmentManager]);
    $manager->assignRole(UserType::DepartmentManager->value);
    $departmentComplaint = Complaint::factory()->create(['company_id' => $company->id, 'department_id' => $department->id]);
    $otherDepartmentComplaint = Complaint::factory()->create(['company_id' => $company->id, 'department_id' => $otherDepartment->id]);

    expect($manager->can('view', $departmentComplaint))->toBeTrue()
        ->and($manager->can('assign', $departmentComplaint))->toBeTrue()
        ->and($manager->can('view', $otherDepartmentComplaint))->toBeFalse()
        ->and($manager->can('assign', $otherDepartmentComplaint))->toBeFalse();
});
