<?php

use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('super admin can manage company settings', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $setting = CompanySetting::factory()->create();
    $superAdmin = User::factory()->create(['user_type' => UserType::SuperAdmin]);
    $superAdmin->assignRole(UserType::SuperAdmin->value);

    expect($superAdmin->can('view', $setting))->toBeTrue()
        ->and($superAdmin->can('update', $setting))->toBeTrue()
        ->and($superAdmin->can('updateTheme', $setting))->toBeTrue()
        ->and($superAdmin->can('updateLanguage', $setting))->toBeTrue();
});

test('company admin can manage own company settings only', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $setting = CompanySetting::factory()->for($company)->create();
    $otherSetting = CompanySetting::factory()->create();
    $companyAdmin = User::factory()->create([
        'company_id' => $company->id,
        'user_type' => UserType::CompanyAdmin,
    ]);
    $companyAdmin->assignRole(UserType::CompanyAdmin->value);

    expect($companyAdmin->can('view', $setting))->toBeTrue()
        ->and($companyAdmin->can('update', $setting))->toBeTrue()
        ->and($companyAdmin->can('updateTheme', $setting))->toBeTrue()
        ->and($companyAdmin->can('updateLanguage', $setting))->toBeTrue()
        ->and($companyAdmin->can('view', $otherSetting))->toBeFalse()
        ->and($companyAdmin->can('update', $otherSetting))->toBeFalse();
});

test('customers cannot manage company settings', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $setting = CompanySetting::factory()->create();
    $customer = User::factory()->create([
        'company_id' => $setting->company_id,
        'user_type' => UserType::Customer,
    ]);
    $customer->assignRole(UserType::Customer->value);

    expect($customer->can('view', $setting))->toBeFalse()
        ->and($customer->can('update', $setting))->toBeFalse();
});
