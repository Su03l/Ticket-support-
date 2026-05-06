<?php

namespace App\Models;

use App\Enums\CompanyThemeMode;
use Database\Factories\CompanySettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['company_id', 'logo_path', 'favicon_path', 'primary_color', 'secondary_color', 'sidebar_color', 'login_branding_enabled', 'login_heading', 'login_subheading', 'default_locale', 'theme_mode', 'metadata'])]
class CompanySetting extends Model
{
    /** @use HasFactory<CompanySettingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'login_branding_enabled' => 'boolean',
            'theme_mode' => CompanyThemeMode::class,
            'metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
