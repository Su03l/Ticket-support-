<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use Database\Factories\ComplaintStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'complaint_id', 'changed_by_id', 'old_status', 'new_status', 'reason'])]
class ComplaintStatusHistory extends Model
{
    /** @use HasFactory<ComplaintStatusHistoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_status' => ComplaintStatus::class,
            'new_status' => ComplaintStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_id');
    }
}
