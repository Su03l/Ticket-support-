<?php

namespace App\Repositories\Contracts;

use App\Models\TicketRating;

interface TicketRatingRepositoryInterface
{
    public function create(array $attributes): TicketRating;
}
