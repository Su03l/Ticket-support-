<?php

namespace App\Repositories;

use App\Models\TicketComment;
use App\Repositories\Contracts\TicketCommentRepositoryInterface;

class TicketCommentRepository implements TicketCommentRepositoryInterface
{
    public function create(array $attributes): TicketComment
    {
        return TicketComment::query()->create($attributes);
    }
}
