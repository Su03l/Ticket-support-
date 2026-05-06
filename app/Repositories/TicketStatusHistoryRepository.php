<?php

namespace App\Repositories;

use App\Models\TicketStatusHistory;
use App\Repositories\Contracts\TicketStatusHistoryRepositoryInterface;

class TicketStatusHistoryRepository implements TicketStatusHistoryRepositoryInterface
{
    public function create(array $attributes): TicketStatusHistory
    {
        return TicketStatusHistory::query()->create($attributes);
    }
}
