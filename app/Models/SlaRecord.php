<?php

namespace App\Models;

use App\Enums\SlaStatus;
use Database\Factories\SlaRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['company_id', 'slable_type', 'slable_id', 'policy_id', 'first_response_due_at', 'resolution_due_at', 'first_responded_at', 'resolved_at', 'breached_first_response_at', 'breached_resolution_at', 'status'])]
class SlaRecord extends Model
{
    /** @use HasFactory<SlaRecordFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'first_response_due_at' => 'datetime',
            'resolution_due_at' => 'datetime',
            'first_responded_at' => 'datetime',
            'resolved_at' => 'datetime',
            'breached_first_response_at' => 'datetime',
            'breached_resolution_at' => 'datetime',
            'status' => SlaStatus::class,
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function policy(): BelongsTo { return $this->belongsTo(SlaPolicy::class, 'policy_id'); }
    public function slable(): MorphTo { return $this->morphTo(); }
}
