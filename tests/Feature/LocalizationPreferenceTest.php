<?php

use App\Models\User;
use Illuminate\Support\Facades\File;

test('authenticated users see their saved arabic locale direction and labels', function () {
    $user = User::factory()->create([
        'locale' => 'ar',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee(__('Dashboard'));
});

test('language switch stores guest locale in session', function () {
    $response = $this
        ->from(route('home'))
        ->post(route('language.switch', 'ar'));

    $response
        ->assertRedirect(route('home'))
        ->assertSessionHas('locale', 'ar');
});

test('language switch updates authenticated user preference', function () {
    $user = User::factory()->create([
        'locale' => 'en',
    ]);

    $response = $this
        ->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('language.switch', 'ar'));

    $response
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('locale', 'ar');

    expect($user->refresh()->locale)->toBe('ar');
});

test('arabic translations cover static translation keys', function () {
    $translations = json_decode(File::get(lang_path('ar.json')), true, flags: JSON_THROW_ON_ERROR);
    $keys = collect([
        resource_path('views'),
        app_path(),
        base_path('routes'),
    ])->flatMap(fn (string $path) => File::allFiles($path))
        ->filter(fn (SplFileInfo $file) => $file->getExtension() === 'php')
        ->flatMap(function (SplFileInfo $file): array {
            preg_match_all('/__\(\s*([\'"])((?:\\\\\1|.)*?)\1\s*\)/s', File::get($file->getPathname()), $matches);

            return array_map(
                fn (string $key): string => str_replace("\\'", "'", $key),
                $matches[2] ?? [],
            );
        })
        ->reject(fn (string $key) => str_contains($key, '$') || str_contains($key, '::'))
        ->unique()
        ->values();

    $missing = $keys->reject(fn (string $key) => array_key_exists($key, $translations))->values();

    expect($missing->all())->toBe([]);
});

test('arabic translations cover enum values and permission labels used in the ui', function () {
    $translations = json_decode(File::get(lang_path('ar.json')), true, flags: JSON_THROW_ON_ERROR);

    $enumValues = collect(File::allFiles(app_path('Enums')))
        ->map(fn (SplFileInfo $file): string => 'App\\Enums\\'.$file->getBasename('.php'))
        ->filter(fn (string $class): bool => enum_exists($class))
        ->flatMap(fn (string $class): array => array_map(
            fn (UnitEnum $case): string => $case->value,
            $class::cases(),
        ));

    $permissionNames = collect(File::allFiles(database_path('seeders')))
        ->flatMap(function (SplFileInfo $file): array {
            preg_match_all('/[a-z_]+\.[a-z_]+(?:\.[a-z_]+)?/', File::get($file->getPathname()), $matches);

            return $matches[0] ?? [];
        })
        ->reject(fn (string $permission): bool => $permission === 'example.com')
        ->unique();

    $keys = $enumValues
        ->flatMap(fn (string $value): array => [$value, str_replace('_', ' ', $value), ucfirst($value)])
        ->merge($permissionNames)
        ->unique()
        ->values();

    $missing = $keys->reject(fn (string $key) => array_key_exists($key, $translations))->values();

    expect($missing->all())->toBe([]);
});
