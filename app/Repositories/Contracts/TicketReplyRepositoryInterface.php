<?php

namespace App\Repositories\Contracts;

use App\Models\TicketReply;

interface TicketReplyRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TicketReply;
}
