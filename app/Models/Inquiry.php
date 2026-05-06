<?php

namespace App\Models;

use App\Enums\InquiryStatus;
use Database\Factories\InquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['company_id', 'department_id', 'customer_id', 'assigned_to_id', 'inquiry_number', 'subject', 'body', 'status', 'converted_ticket_id', 'closed_at'])]
class Inquiry extends Model
{
    /** @use HasFactory<InquiryFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => InquiryStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
    public function assignedAgent(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to_id'); }
    public function convertedTicket(): BelongsTo { return $this->belongsTo(Ticket::class, 'converted_ticket_id'); }
    public function replies(): HasMany { return $this->hasMany(InquiryReply::class); }
    public function statusHistories(): HasMany { return $this->hasMany(InquiryStatusHistory::class); }
    public function slaRecord(): MorphOne { return $this->morphOne(SlaRecord::class, 'slable'); }

    public function isClosed(): bool
    {
        return in_array($this->status, [InquiryStatus::Closed, InquiryStatus::Cancelled, InquiryStatus::ConvertedToTicket], true);
    }
}
