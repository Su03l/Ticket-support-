<?php

namespace App\Models;

use App\Enums\NpsCategory;
use Database\Factories\CustomerSatisfactionSurveyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'ticket_id', 'customer_id', 'agent_id', 'department_id', 'csat_score', 'nps_score', 'nps_category', 'feedback', 'sent_at', 'submitted_at'])]
class CustomerSatisfactionSurvey extends Model
{
    /** @use HasFactory<CustomerSatisfactionSurveyFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'csat_score' => 'integer',
            'nps_score' => 'integer',
            'nps_category' => NpsCategory::class,
            'sent_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
