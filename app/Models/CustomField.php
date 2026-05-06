<?php

namespace App\Models;

use App\Enums\CustomFieldAppliesTo;
use App\Enums\CustomFieldType;
use Database\Factories\CustomFieldFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'applies_to', 'label', 'key', 'type', 'options', 'validation_rules', 'is_required', 'is_active', 'sort_order'])]
class CustomField extends Model
{
    /** @use HasFactory<CustomFieldFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'applies_to' => CustomFieldAppliesTo::class,
            'type' => CustomFieldType::class,
            'options' => 'array',
            'validation_rules' => 'array',
            'is_required' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function values(): HasMany { return $this->hasMany(CustomFieldValue::class); }
}
