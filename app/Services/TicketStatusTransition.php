<?php

namespace App\Services;

use App\Enums\TicketStatus;
use InvalidArgumentException;

class TicketStatusTransition
{
    public function ensureAllowed(TicketStatus $from, TicketStatus $to, ?string $reason = null): void
    {
        if ($from === TicketStatus::Closed && $to !== TicketStatus::Reopened) {
            throw new InvalidArgumentException('Closed tickets must be reopened before another status change.');
        }

        if (in_array($to, [TicketStatus::Cancelled, TicketStatus::Reopened], true) && blank($reason)) {
            throw new InvalidArgumentException('A reason is required for this ticket status change.');
        }
    }
}
