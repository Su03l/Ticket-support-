<?php

namespace App\Models;

use App\Enums\AttachmentVisibility;
use Database\Factories\AttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['company_id', 'uploaded_by_id', 'attachable_type', 'attachable_id', 'original_name', 'stored_name', 'path', 'disk', 'mime_type', 'size', 'visibility'])]
class Attachment extends Model
{
    /** @use HasFactory<AttachmentFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => AttachmentVisibility::class,
            'size' => 'integer',
        ];
    }

    // the relationships between the tables with the comment  
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
