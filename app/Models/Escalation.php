<?php

namespace App\Models;

use App\Enums\EscalationStatus;
use Database\Factories\EscalationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['company_id', 'escalatable_type', 'escalatable_id', 'escalated_by_id', 'escalated_to_id', 'reason', 'status', 'escalated_at', 'resolved_at'])]
class Escalation extends Model
{
    /** @use HasFactory<EscalationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => EscalationStatus::class,
            'escalated_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function escalatable(): MorphTo { return $this->morphTo(); }
    public function escalatedBy(): BelongsTo { return $this->belongsTo(User::class, 'escalated_by_id'); }
    public function escalatedTo(): BelongsTo { return $this->belongsTo(User::class, 'escalated_to_id'); }
}
