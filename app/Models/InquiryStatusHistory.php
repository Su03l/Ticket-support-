<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use Database\Factories\InquiryStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'inquiry_id', 'changed_by_id', 'old_status', 'new_status', 'reason'])]
class InquiryStatusHistory extends Model
{
    /** @use HasFactory<InquiryStatusHistoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'old_status' => InquiryStatus::class,
            'new_status' => InquiryStatus::class,
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function inquiry(): BelongsTo { return $this->belongsTo(Inquiry::class); }
    public function changedBy(): BelongsTo { return $this->belongsTo(User::class, 'changed_by_id'); }
}
