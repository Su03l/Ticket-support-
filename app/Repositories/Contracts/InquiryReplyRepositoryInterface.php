<?php

namespace App\Repositories\Contracts;

use App\Models\InquiryReply;

interface InquiryReplyRepositoryInterface
{
    public function create(array $attributes): InquiryReply;
}
