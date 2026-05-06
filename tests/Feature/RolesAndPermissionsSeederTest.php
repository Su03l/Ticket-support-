<?php

use App\Enums\UserType;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('roles and permissions seeder is idempotent', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Permission::count())->toBe(87)
        ->and(Role::count())->toBe(6)
        ->and(User::where('email', 'super.admin@example.com')->count())->toBe(1)
        ->and(User::where('email', 'company.admin@example.com')->count())->toBe(1)
        ->and(Company::where('slug', 'demo-company')->count())->toBe(1);
});

test('super admin role receives every permission', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $superAdminRole = Role::findByName(UserType::SuperAdmin->value);

    expect($superAdminRole->permissions)->toHaveCount(Permission::count())
        ->and(User::where('email', 'super.admin@example.com')->firstOrFail()->hasRole(UserType::SuperAdmin->value))->toBeTrue();
});

test('company admin excludes risky platform permissions', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $companyAdminRole = Role::findByName(UserType::CompanyAdmin->value);
    $companyAdmin = User::where('email', 'company.admin@example.com')->firstOrFail();

    expect($companyAdminRole->hasPermissionTo('companies.view'))->toBeTrue()
        ->and($companyAdminRole->hasPermissionTo('companies.delete'))->toBeFalse()
        ->and($companyAdminRole->hasPermissionTo('error_logs.view'))->toBeFalse()
        ->and($companyAdmin->hasRole(UserType::CompanyAdmin->value))->toBeTrue()
        ->and($companyAdmin->company)->not->toBeNull();
});

test('customer role receives own request permissions only', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $customerRole = Role::findByName(UserType::Customer->value);

    expect($customerRole->hasPermissionTo('tickets.view.own'))->toBeTrue()
        ->and($customerRole->hasPermissionTo('tickets.view.department'))->toBeFalse()
        ->and($customerRole->hasPermissionTo('tickets.assign'))->toBeFalse()
        ->and($customerRole->hasPermissionTo('complaints.view.own'))->toBeTrue()
        ->and($customerRole->hasPermissionTo('inquiries.view.own'))->toBeTrue();
});
