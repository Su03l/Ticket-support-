<?php

namespace App\Models;

use Database\Factories\FileUploadPolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'allowed_mime_types', 'max_file_size_kb', 'max_files_per_request', 'allow_public_attachments', 'allow_internal_attachments'])]
class FileUploadPolicy extends Model
{
    /** @use HasFactory<FileUploadPolicyFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'allowed_mime_types' => 'array',
            'max_file_size_kb' => 'integer',
            'max_files_per_request' => 'integer',
            'allow_public_attachments' => 'boolean',
            'allow_internal_attachments' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
