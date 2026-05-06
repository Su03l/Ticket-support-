<?php

namespace Database\Factories;

use App\Enums\CompanyThemeMode;
use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanySetting>
 */
class CompanySettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'logo_path' => null,
            'favicon_path' => null,
            'primary_color' => '#2563eb',
            'secondary_color' => '#0f172a',
            'sidebar_color' => '#ffffff',
            'login_branding_enabled' => true,
            'login_heading' => fake()->company(),
            'login_subheading' => fake()->catchPhrase(),
            'default_locale' => 'ar',
            'theme_mode' => CompanyThemeMode::System,
            'metadata' => null,
        ];
    }
}
