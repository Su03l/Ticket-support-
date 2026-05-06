<?php

use App\Enums\UserType;
use App\Models\Company;
use App\Models\MailboxMessage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('recipient can view and read their own mailbox message', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $recipient = User::factory()->create([
        'company_id' => $company->id,
        'user_type' => UserType::Customer,
    ]);
    $recipient->assignRole(UserType::Customer->value);

    $message = MailboxMessage::factory()->create([
        'company_id' => $company->id,
        'recipient_id' => $recipient->id,
    ]);

    expect($recipient->can('view', $message))->toBeTrue()
        ->and($recipient->can('read', $message))->toBeTrue()
        ->and($recipient->can('archive', $message))->toBeFalse()
        ->and($recipient->can('delete', $message))->toBeFalse();
});

test('users cannot view another recipients mailbox message', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $recipient = User::factory()->create(['company_id' => $company->id]);
    $otherUser = User::factory()->create([
        'company_id' => $company->id,
        'user_type' => UserType::CompanyAdmin,
    ]);
    $otherUser->assignRole(UserType::CompanyAdmin->value);

    $message = MailboxMessage::factory()->create([
        'company_id' => $company->id,
        'recipient_id' => $recipient->id,
    ]);

    expect($otherUser->can('view', $message))->toBeFalse()
        ->and($otherUser->can('read', $message))->toBeFalse()
        ->and($otherUser->can('archive', $message))->toBeFalse()
        ->and($otherUser->can('delete', $message))->toBeFalse();
});

test('recipient cannot view a mailbox message from another tenant', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $recipientCompany = Company::factory()->create();
    $messageCompany = Company::factory()->create();
    $recipient = User::factory()->create([
        'company_id' => $recipientCompany->id,
        'user_type' => UserType::CompanyAdmin,
    ]);
    $recipient->assignRole(UserType::CompanyAdmin->value);

    $message = MailboxMessage::factory()->create([
        'company_id' => $messageCompany->id,
        'recipient_id' => $recipient->id,
    ]);

    expect($recipient->can('view', $message))->toBeFalse();
});

test('company admin can archive and delete their own mailbox message', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create(['user_type' => UserType::CompanyAdmin]);
    $user->assignRole(UserType::CompanyAdmin->value);

    $message = MailboxMessage::factory()->create([
        'company_id' => $user->company_id,
        'recipient_id' => $user->id,
    ]);

    expect($user->can('archive', $message))->toBeTrue()
        ->and($user->can('delete', $message))->toBeTrue();
});
