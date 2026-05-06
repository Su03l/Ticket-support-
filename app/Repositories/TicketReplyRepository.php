<?php

namespace App\Repositories;

use App\Models\TicketReply;
use App\Repositories\Contracts\TicketReplyRepositoryInterface;

class TicketReplyRepository implements TicketReplyRepositoryInterface
{
    public function create(array $attributes): TicketReply
    {
        return TicketReply::query()->create($attributes);
    }
}
