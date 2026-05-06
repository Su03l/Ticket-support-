<?php

namespace App\Repositories;

use App\Models\TicketTransfer;
use App\Repositories\Contracts\TicketTransferRepositoryInterface;

class TicketTransferRepository implements TicketTransferRepositoryInterface
{
    public function create(array $attributes): TicketTransfer
    {
        return TicketTransfer::query()->create($attributes);
    }
}
