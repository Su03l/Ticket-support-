<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketRating;
use App\Models\User;
use App\Repositories\Contracts\TicketRatingRepositoryInterface;
use InvalidArgumentException;

class TicketRatingService
{
    public function __construct(private TicketRatingRepositoryInterface $ratings) {}

    public function rate(Ticket $ticket, User $customer, int $rating, ?string $feedback = null): TicketRating
    {
        if ($ticket->customer_id !== $customer->id || $ticket->status !== TicketStatus::Closed) {
            throw new InvalidArgumentException('Only the customer can rate their own closed ticket.');
        }

        if ($ticket->rating()->exists()) {
            throw new InvalidArgumentException('Ticket has already been rated.');
        }

        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating must be between 1 and 5.');
        }

        $ticketRating = $this->ratings->create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'customer_id' => $customer->id,
            'rating' => $rating,
            'feedback' => $feedback,
            'submitted_at' => now(),
        ]);

        activity()->performedOn($ticketRating)->causedBy($customer)->event('ticket.rated')->log('Ticket rated');

        return $ticketRating;
    }
}
