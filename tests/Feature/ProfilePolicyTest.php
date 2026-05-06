<?php

use App\Enums\UserType;
use App\Models\Company;
use App\Models\User;

test('users can update their own profile', function () {
    $user = User::factory()->create();

    expect($user->can('update', $user))->toBeTrue()
        ->and($user->can('updateAvatar', $user))->toBeTrue()
        ->and($user->can('updatePreferences', $user))->toBeTrue();
});

test('super admins can update any profile', function () {
    $superAdmin = User::factory()->create(['user_type' => UserType::SuperAdmin]);
    $user = User::factory()->create();

    expect($superAdmin->can('update', $user))->toBeTrue()
        ->and($superAdmin->can('updateAvatar', $user))->toBeTrue()
        ->and($superAdmin->can('updatePreferences', $user))->toBeTrue();
});

test('company admins can update company user profiles but not preferences or super admins', function () {
    $company = Company::factory()->create();
    $companyAdmin = User::factory()->create([
        'company_id' => $company->id,
        'user_type' => UserType::CompanyAdmin,
    ]);
    $companyUser = User::factory()->create(['company_id' => $company->id]);
    $superAdmin = User::factory()->create(['user_type' => UserType::SuperAdmin]);
    $otherCompanyUser = User::factory()->create();

    expect($companyAdmin->can('update', $companyUser))->toBeTrue()
        ->and($companyAdmin->can('updateAvatar', $companyUser))->toBeTrue()
        ->and($companyAdmin->can('updatePreferences', $companyUser))->toBeFalse()
        ->and($companyAdmin->can('update', $superAdmin))->toBeFalse()
        ->and($companyAdmin->can('update', $otherCompanyUser))->toBeFalse();
});
