<?php

namespace App\Repositories;

use App\Models\TicketRating;
use App\Repositories\Contracts\TicketRatingRepositoryInterface;

class TicketRatingRepository implements TicketRatingRepositoryInterface
{
    public function create(array $attributes): TicketRating
    {
        return TicketRating::query()->create($attributes);
    }
}
