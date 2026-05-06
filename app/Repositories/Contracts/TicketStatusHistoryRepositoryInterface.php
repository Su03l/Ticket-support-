<?php

namespace App\Repositories\Contracts;

use App\Models\TicketStatusHistory;

interface TicketStatusHistoryRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TicketStatusHistory;
}
