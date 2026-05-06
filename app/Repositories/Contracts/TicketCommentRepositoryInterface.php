<?php

namespace App\Repositories\Contracts;

use App\Models\TicketComment;

interface TicketCommentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TicketComment;
}
