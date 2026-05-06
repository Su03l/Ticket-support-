<?php

use App\Enums\ThemePreference;
use App\Models\Company;
use App\Models\User;
use App\Services\UserProfileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile service updates profile and clears verification when email changes', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $updatedUser = app(UserProfileService::class)->updateProfile($user, [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);

    expect($updatedUser->name)->toBe('Updated Name')
        ->and($updatedUser->email)->toBe('updated@example.com')
        ->and($updatedUser->email_verified_at)->toBeNull();
});

test('profile service stores and replaces avatars on the public disk', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $service = app(UserProfileService::class);

    $user = $service->updateAvatar($user, UploadedFile::fake()->image('avatar.jpg', 128, 128));
    $firstAvatar = $user->avatar;

    Storage::disk('public')->assertExists($firstAvatar);

    $user = $service->updateAvatar($user, UploadedFile::fake()->image('avatar.png', 128, 128));

    Storage::disk('public')->assertMissing($firstAvatar);
    Storage::disk('public')->assertExists($user->avatar);
});

test('profile service removes avatars', function () {
    Storage::fake('public');

    $user = app(UserProfileService::class)->updateAvatar(
        User::factory()->create(),
        UploadedFile::fake()->image('avatar.jpg', 128, 128),
    );

    $avatar = $user->avatar;

    $user = app(UserProfileService::class)->deleteAvatar($user);

    expect($user->avatar)->toBeNull();
    Storage::disk('public')->assertMissing($avatar);
});

test('profile service persists preferences with defaults', function () {
    $user = User::factory()->create([
        'locale' => 'ar',
        'theme_preference' => ThemePreference::System,
        'notification_preferences' => null,
    ]);

    $user = app(UserProfileService::class)->updatePreferences($user, [
        'locale' => 'en',
        'theme_preference' => ThemePreference::Dark,
        'notification_preferences' => [
            'email' => false,
        ],
    ]);

    expect($user->locale)->toBe('en')
        ->and($user->theme_preference)->toBe(ThemePreference::Dark)
        ->and($user->notification_preferences)->toBe([
            'email' => false,
            'database' => true,
            'realtime' => true,
        ]);
});
