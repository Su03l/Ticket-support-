<?php

use App\Enums\ThemePreference;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('profile.edit'))->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
});

test('email verification status is unchanged when email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.profile')
        ->set('name', 'Test User')
        ->set('email', $user->email)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('avatar can be uploaded from profile settings', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('avatarUpload', UploadedFile::fake()->image('avatar.jpg', 128, 128))
        ->call('updateAvatar')
        ->assertHasNoErrors();

    expect($user->refresh()->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($user->avatar);
});

test('avatar upload must be a safe profile image', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('avatarUpload', UploadedFile::fake()->create('avatar.svg', 10, 'image/svg+xml'))
        ->call('updateAvatar')
        ->assertHasErrors(['avatarUpload']);

    expect($user->refresh()->avatar)->toBeNull();
});

test('profile preferences can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('locale', 'en')
        ->set('notificationPreferences.email', false)
        ->set('notificationPreferences.database', true)
        ->set('notificationPreferences.realtime', false)
        ->call('updatePreferences')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->locale)->toBe('en')
        ->and($user->notification_preferences)->toBe([
            'email' => false,
            'database' => true,
            'realtime' => false,
        ]);
});

test('appearance preference can be persisted', function () {
    $user = User::factory()->create(['theme_preference' => ThemePreference::System]);

    $this->actingAs($user);

    Livewire::test('pages::settings.appearance')
        ->set('themePreference', 'dark')
        ->call('updateAppearance')
        ->assertHasNoErrors();

    expect($user->refresh()->theme_preference)->toBe(ThemePreference::Dark);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'password')
        ->call('deleteUser');

    $response
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = Livewire::test('pages::settings.delete-user-modal')
        ->set('password', 'wrong-password')
        ->call('deleteUser');

    $response->assertHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
});
