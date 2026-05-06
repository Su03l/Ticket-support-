<?php

use App\Enums\UserType;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

test('authenticated shell renders navbar placeholders and user profile menu', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create([
        'locale' => 'en',
        'user_type' => UserType::Customer,
    ]);
    $user->assignRole(UserType::Customer->value);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Notifications')
        ->assertSee('Mailbox')
        ->assertSee('Profile')
        ->assertSee('Account settings')
        ->assertSee($user->email);
});

test('company admin sees permission aware organization navigation', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'locale' => 'en',
        'user_type' => UserType::CompanyAdmin,
    ]);
    $user->assignRole(UserType::CompanyAdmin->value);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Companies')
        ->assertSee('Users')
        ->assertSee('Departments')
        ->assertSee('Roles')
        ->assertSee('Reports')
        ->assertSee('Settings')
        ->assertSee($company->name);
});

test('customer sees own request navigation but not admin organization links', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create([
        'locale' => 'en',
        'user_type' => UserType::Customer,
    ]);
    $user->assignRole(UserType::Customer->value);

    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Tickets')
        ->assertSee('Complaints')
        ->assertSee('Inquiries')
        ->assertDontSee('Companies')
        ->assertDontSee('Departments')
        ->assertDontSee('Error logs');
});
