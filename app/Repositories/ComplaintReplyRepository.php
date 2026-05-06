<?php

namespace App\Repositories;

use App\Models\ComplaintReply;
use App\Repositories\Contracts\ComplaintReplyRepositoryInterface;

class ComplaintReplyRepository implements ComplaintReplyRepositoryInterface
{
    public function create(array $attributes): ComplaintReply
    {
        return ComplaintReply::query()->create($attributes);
    }
}
