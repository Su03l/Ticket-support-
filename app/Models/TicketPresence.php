<?php

namespace App\Models;

use App\Enums\TicketPresenceAction;
use Database\Factories\TicketPresenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'ticket_id', 'user_id', 'action', 'last_seen_at'])]
class TicketPresence extends Model
{
    /** @use HasFactory<TicketPresenceFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'action' => TicketPresenceAction::class,
            'last_seen_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
