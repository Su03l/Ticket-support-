<?php

namespace App\Models;

use Database\Factories\EmployeeKpiTargetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'user_id', 'managed_by_id', 'month', 'year', 'tickets_resolved_target', 'first_response_minutes_target', 'csat_target', 'quality_score_target', 'manual_adjustments'])]
class EmployeeKpiTarget extends Model
{
    /** @use HasFactory<EmployeeKpiTargetFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'year' => 'integer',
            'tickets_resolved_target' => 'integer',
            'first_response_minutes_target' => 'integer',
            'csat_target' => 'decimal:2',
            'quality_score_target' => 'decimal:2',
            'manual_adjustments' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function managedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'managed_by_id');
    }
}
