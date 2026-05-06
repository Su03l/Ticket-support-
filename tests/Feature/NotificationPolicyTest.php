<?php

use App\Enums\UserType;
use App\Models\Company;
use App\Models\SupportNotification;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('recipient with permissions can view mark and delete own notifications', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $user->assignRole(UserType::Customer->value);
    $notification = SupportNotification::factory()->create(['recipient_id' => $user->id, 'company_id' => $company->id]);

    expect($user->can('viewAny', SupportNotification::class))->toBeTrue()
        ->and($user->can('view', $notification))->toBeTrue()
        ->and($user->can('markRead', $notification))->toBeTrue()
        ->and($user->can('delete', $notification))->toBeFalse();
});

test('users cannot access notifications for another recipient or company', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $otherCompany = Company::factory()->create();
    $user = User::factory()->create(['company_id' => $company->id, 'user_type' => UserType::Customer]);
    $user->assignRole(UserType::Customer->value);
    $otherUserNotification = SupportNotification::factory()->create(['company_id' => $company->id]);
    $otherCompanyNotification = SupportNotification::factory()->create([
        'recipient_id' => $user->id,
        'company_id' => $otherCompany->id,
    ]);

    expect($user->can('view', $otherUserNotification))->toBeFalse()
        ->and($user->can('view', $otherCompanyNotification))->toBeFalse();
});

test('company admin can delete own notifications because role has delete permission', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $companyAdmin = User::factory()->create([
        'company_id' => $company->id,
        'user_type' => UserType::CompanyAdmin,
    ]);
    $companyAdmin->assignRole(UserType::CompanyAdmin->value);
    $notification = SupportNotification::factory()->create(['recipient_id' => $companyAdmin->id, 'company_id' => $company->id]);

    expect($companyAdmin->can('delete', $notification))->toBeTrue();
});
