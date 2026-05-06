<?php

namespace App\Services;

use App\Models\Ticket;

class TicketNumberGenerator
{
    public function generate(): string
    {
        $prefix = 'TCK-'.now()->format('Ymd');
        $sequence = Ticket::withTrashed()
            ->where('ticket_number', 'like', "{$prefix}-%")
            ->count() + 1;

        do {
            $ticketNumber = $prefix.'-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
            $sequence++;
        } while (Ticket::withTrashed()->where('ticket_number', $ticketNumber)->exists());

        return $ticketNumber;
    }
}
