<?php

use App\Enums\UserType;
use App\Models\Company;
use App\Models\User;

test('super admin can manage companies', function () {
    $superAdmin = User::factory()->create(['user_type' => UserType::SuperAdmin]);
    $company = Company::factory()->create();

    expect($superAdmin->can('viewAny', Company::class))->toBeTrue()
        ->and($superAdmin->can('view', $company))->toBeTrue()
        ->and($superAdmin->can('create', Company::class))->toBeTrue()
        ->and($superAdmin->can('update', $company))->toBeTrue()
        ->and($superAdmin->can('delete', $company))->toBeTrue();
});

test('company admin can only view and update their own company', function () {
    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $companyAdmin = User::factory()->create([
        'company_id' => $company->id,
        'user_type' => UserType::CompanyAdmin,
    ]);

    expect($companyAdmin->can('viewAny', Company::class))->toBeTrue()
        ->and($companyAdmin->can('view', $company))->toBeTrue()
        ->and($companyAdmin->can('update', $company))->toBeTrue()
        ->and($companyAdmin->can('view', $otherCompany))->toBeFalse()
        ->and($companyAdmin->can('update', $otherCompany))->toBeFalse()
        ->and($companyAdmin->can('create', Company::class))->toBeFalse()
        ->and($companyAdmin->can('delete', $company))->toBeFalse();
});

test('customers cannot manage companies', function () {
    $customer = User::factory()->create(['user_type' => UserType::Customer]);
    $company = Company::factory()->create();

    expect($customer->can('viewAny', Company::class))->toBeFalse()
        ->and($customer->can('view', $company))->toBeFalse()
        ->and($customer->can('create', Company::class))->toBeFalse()
        ->and($customer->can('update', $company))->toBeFalse()
        ->and($customer->can('delete', $company))->toBeFalse();
});
