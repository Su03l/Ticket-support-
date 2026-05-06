<?php

use App\Enums\CompanyThemeMode;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use App\Services\CompanySettingsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('company settings service creates defaults and updates presentation', function () {
    $company = Company::factory()->create();
    $service = app(CompanySettingsService::class);

    $settings = $service->settingsFor($company);

    expect($settings->primary_color)->toBe('#2563eb')
        ->and($settings->default_locale)->toBe('ar')
        ->and($settings->theme_mode)->toBe(CompanyThemeMode::System);

    $settings = $service->updatePresentation($company, [
        'primary_color' => '#111827',
        'secondary_color' => '#f97316',
        'sidebar_color' => '#0f172a',
        'login_branding_enabled' => false,
        'login_heading' => 'Support Portal',
        'login_subheading' => 'We are here to help',
        'default_locale' => 'en',
        'theme_mode' => CompanyThemeMode::Dark,
    ]);

    expect($settings->primary_color)->toBe('#111827')
        ->and($settings->default_locale)->toBe('en')
        ->and($settings->theme_mode)->toBe(CompanyThemeMode::Dark)
        ->and($settings->login_branding_enabled)->toBeFalse();
});

test('company settings service stores and replaces branding files', function () {
    Storage::fake('public');

    $company = Company::factory()->create();
    $service = app(CompanySettingsService::class);

    $settings = $service->updateLogo($company, UploadedFile::fake()->image('logo.png', 256, 128));
    $firstLogo = $settings->logo_path;

    Storage::disk('public')->assertExists($firstLogo);

    $settings = $service->updateLogo($company, UploadedFile::fake()->image('logo-new.png', 256, 128));

    Storage::disk('public')->assertMissing($firstLogo);
    Storage::disk('public')->assertExists($settings->logo_path);

    $settings = $service->updateFavicon($company, UploadedFile::fake()->image('favicon.png', 64, 64));

    Storage::disk('public')->assertExists($settings->favicon_path);
});

test('company settings page can update profile and presentation', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'user_type' => UserType::CompanyAdmin,
    ]);
    $user->assignRole(UserType::CompanyAdmin->value);

    $this->actingAs($user);

    Livewire::test('pages::settings.company')
        ->set('name', 'Updated Company')
        ->set('email', 'updated@example.com')
        ->set('website', 'https://example.com')
        ->call('updateCompanyProfile')
        ->assertHasNoErrors()
        ->set('primaryColor', '#111827')
        ->set('secondaryColor', '#f97316')
        ->set('sidebarColor', '#0f172a')
        ->set('loginBrandingEnabled', false)
        ->set('loginHeading', 'Support Portal')
        ->set('loginSubheading', 'Welcome')
        ->set('defaultLocale', 'en')
        ->set('themeMode', 'dark')
        ->call('updatePresentation')
        ->assertHasNoErrors();

    $company->refresh();
    $settings = CompanySetting::whereBelongsTo($company)->firstOrFail();

    expect($company->name)->toBe('Updated Company')
        ->and($settings->primary_color)->toBe('#111827')
        ->and($settings->default_locale)->toBe('en')
        ->and($settings->theme_mode)->toBe(CompanyThemeMode::Dark);
});

test('company settings page validates branding uploads', function () {
    Storage::fake('public');

    $this->seed(RolesAndPermissionsSeeder::class);

    $company = Company::factory()->create();
    $user = User::factory()->create([
        'company_id' => $company->id,
        'user_type' => UserType::CompanyAdmin,
    ]);
    $user->assignRole(UserType::CompanyAdmin->value);

    $this->actingAs($user);

    Livewire::test('pages::settings.company')
        ->set('logoUpload', UploadedFile::fake()->create('logo.svg', 20, 'image/svg+xml'))
        ->call('updateLogo')
        ->assertHasErrors(['logoUpload'])
        ->set('logoUpload', UploadedFile::fake()->image('logo.png', 256, 128))
        ->call('updateLogo')
        ->assertHasNoErrors()
        ->set('faviconUpload', UploadedFile::fake()->image('favicon.png', 64, 64))
        ->call('updateFavicon')
        ->assertHasNoErrors();

    $settings = app(CompanySettingsService::class)->settingsFor($company);

    Storage::disk('public')->assertExists($settings->logo_path);
    Storage::disk('public')->assertExists($settings->favicon_path);
});
