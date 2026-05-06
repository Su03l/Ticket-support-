<?php

namespace App\Repositories\Contracts;

use App\Models\ComplaintReply;

interface ComplaintReplyRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ComplaintReply;
}
