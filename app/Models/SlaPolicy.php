<?php

namespace App\Models;

use App\Enums\SlaAppliesTo;
use Database\Factories\SlaPolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'name', 'applies_to', 'priority_id', 'first_response_minutes', 'resolution_minutes', 'escalation_minutes', 'is_active'])]
class SlaPolicy extends Model
{
    /** @use HasFactory<SlaPolicyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'applies_to' => SlaAppliesTo::class,
            'first_response_minutes' => 'integer',
            'resolution_minutes' => 'integer',
            'escalation_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function priority(): BelongsTo { return $this->belongsTo(TicketPriority::class, 'priority_id'); }
    public function records(): HasMany { return $this->hasMany(SlaRecord::class, 'policy_id'); }
}
