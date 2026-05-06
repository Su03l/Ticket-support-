<?php

namespace App\Policies;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketRating;
use App\Models\User;

class TicketRatingPolicy
{
    public function create(User $user, Ticket $ticket): bool
    {
        return $ticket->customer_id === $user->id && $ticket->status === TicketStatus::Closed && ! $ticket->rating()->exists();
    }

    public function view(User $user, TicketRating $rating): bool
    {
        return $user->can('view', $rating->ticket);
    }
}
