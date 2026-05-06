<?php

namespace App\Services;

use App\Enums\CompanyThemeMode;
use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CompanySettingsService
{
    public function settingsFor(Company $company): CompanySetting
    {
        return CompanySetting::query()->firstOrCreate([
            'company_id' => $company->id,
        ], $this->defaultSettings());
    }

    /**
     * @param  array{name?: string, email?: string|null, phone?: string|null, website?: string|null}  $attributes
     */
    public function updateCompanyProfile(Company $company, array $attributes): Company
    {
        $company->update($attributes);

        return $company->refresh();
    }

    /**
     * @param  array{primary_color?: string, secondary_color?: string, sidebar_color?: string, login_branding_enabled?: bool, login_heading?: string|null, login_subheading?: string|null, default_locale?: string, theme_mode?: CompanyThemeMode|string}  $attributes
     */
    public function updatePresentation(Company $company, array $attributes): CompanySetting
    {
        $settings = $this->settingsFor($company);
        $settings->update($attributes);

        return $settings->refresh();
    }

    public function updateLogo(Company $company, UploadedFile $logo): CompanySetting
    {
        $settings = $this->settingsFor($company);
        $previousLogo = $settings->logo_path;
        $path = $logo->store("companies/{$company->id}/branding", 'public');

        $settings->forceFill([
            'logo_path' => $path,
        ])->save();

        if ($previousLogo !== null && $previousLogo !== $path) {
            Storage::disk('public')->delete($previousLogo);
        }

        return $settings->refresh();
    }

    public function updateFavicon(Company $company, UploadedFile $favicon): CompanySetting
    {
        $settings = $this->settingsFor($company);
        $previousFavicon = $settings->favicon_path;
        $path = $favicon->store("companies/{$company->id}/branding", 'public');

        $settings->forceFill([
            'favicon_path' => $path,
        ])->save();

        if ($previousFavicon !== null && $previousFavicon !== $path) {
            Storage::disk('public')->delete($previousFavicon);
        }

        return $settings->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSettings(): array
    {
        return [
            'primary_color' => '#2563eb',
            'secondary_color' => '#0f172a',
            'sidebar_color' => '#ffffff',
            'login_branding_enabled' => true,
            'default_locale' => 'ar',
            'theme_mode' => CompanyThemeMode::System,
        ];
    }
}
