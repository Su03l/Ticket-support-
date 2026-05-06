<?php

namespace App\Models;

use Database\Factories\TicketRatingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'ticket_id', 'customer_id', 'rating', 'feedback', 'submitted_at'])]
class TicketRating extends Model
{
    /** @use HasFactory<TicketRatingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['rating' => 'integer', 'submitted_at' => 'datetime'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
}
