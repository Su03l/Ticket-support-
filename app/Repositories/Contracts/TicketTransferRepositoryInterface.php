<?php

namespace App\Repositories\Contracts;

use App\Models\TicketTransfer;

interface TicketTransferRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TicketTransfer;
}
