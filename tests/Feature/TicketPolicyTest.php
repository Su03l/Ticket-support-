<?php

use App\Enums\UserType;
use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('ticket policy enforces customer own ticket visibility', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $customer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $otherCustomer = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $customer->assignRole(UserType::Customer->value);
    $otherCustomer->assignRole(UserType::Customer->value);
    $ticket = Ticket::factory()->forCompanyDepartmentCustomer($company, $department, $customer)->create();

    expect($customer->can('view', $ticket))->toBeTrue()
        ->and($otherCustomer->can('view', $ticket))->toBeFalse();
});

test('company admin can view company tickets but not another company ticket', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $admin = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::CompanyAdmin]);
    $admin->assignRole(UserType::CompanyAdmin->value);
    $companyTicket = Ticket::factory()->create(['company_id' => $company->id]);
    $otherTicket = Ticket::factory()->create();

    expect($admin->can('view', $companyTicket))->toBeTrue()
        ->and($admin->can('view', $otherTicket))->toBeFalse();
});

test('department manager can assign department tickets only', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $department = Department::factory()->create(['company_id' => $company->id]);
    $otherDepartment = Department::factory()->create(['company_id' => $company->id]);
    $manager = User::factory()->create(['company_id' => $company->id, 'department_id' => $department->id, 'user_type' => UserType::DepartmentManager]);
    $manager->assignRole(UserType::DepartmentManager->value);
    $departmentTicket = Ticket::factory()->create(['company_id' => $company->id, 'department_id' => $department->id]);
    $otherDepartmentTicket = Ticket::factory()->create(['company_id' => $company->id, 'department_id' => $otherDepartment->id]);

    expect($manager->can('assign', $departmentTicket))->toBeTrue()
        ->and($manager->can('assign', $otherDepartmentTicket))->toBeFalse();
});
