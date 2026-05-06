<?php

namespace App\Repositories;

use App\Models\InquiryStatusHistory;
use App\Repositories\Contracts\InquiryStatusHistoryRepositoryInterface;

class InquiryStatusHistoryRepository implements InquiryStatusHistoryRepositoryInterface
{
    public function create(array $attributes): InquiryStatusHistory
    {
        return InquiryStatusHistory::query()->create($attributes);
    }
}
