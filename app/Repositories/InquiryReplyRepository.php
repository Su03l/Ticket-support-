<?php

namespace App\Repositories;

use App\Models\InquiryReply;
use App\Repositories\Contracts\InquiryReplyRepositoryInterface;

class InquiryReplyRepository implements InquiryReplyRepositoryInterface
{
    public function create(array $attributes): InquiryReply
    {
        return InquiryReply::query()->create($attributes);
    }
}
