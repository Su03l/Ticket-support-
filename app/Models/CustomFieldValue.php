<?php

namespace App\Models;

use Database\Factories\CustomFieldValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['company_id', 'custom_field_id', 'fieldable_type', 'fieldable_id', 'value'])]
class CustomFieldValue extends Model
{
    /** @use HasFactory<CustomFieldValueFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function customField(): BelongsTo { return $this->belongsTo(CustomField::class); }
    public function fieldable(): MorphTo { return $this->morphTo(); }
}
