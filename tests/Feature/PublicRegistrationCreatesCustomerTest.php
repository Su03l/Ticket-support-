<?php

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;

test('public registration creates an active customer user', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Public Customer',
        'email' => 'customer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'customer@example.com')->firstOrFail();

    expect($user->user_type)->toBe(UserType::Customer)
        ->and($user->status)->toBe(UserStatus::Active)
        ->and($user->locale)->toBe('ar')
        ->and($user->company_id)->toBeNull()
        ->and($user->department_id)->toBeNull();
});
