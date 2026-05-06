<?php

namespace App\Repositories\Contracts;

use App\Models\TicketAssignment;

interface TicketAssignmentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TicketAssignment;
}
