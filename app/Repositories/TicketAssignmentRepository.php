<?php

namespace App\Repositories;

use App\Models\TicketAssignment;
use App\Repositories\Contracts\TicketAssignmentRepositoryInterface;

class TicketAssignmentRepository implements TicketAssignmentRepositoryInterface
{
    public function create(array $attributes): TicketAssignment
    {
        return TicketAssignment::query()->create($attributes);
    }
}
