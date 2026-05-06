<?php

namespace App\Repositories\Contracts;

use App\Models\InquiryStatusHistory;

interface InquiryStatusHistoryRepositoryInterface
{
    public function create(array $attributes): InquiryStatusHistory;
}
