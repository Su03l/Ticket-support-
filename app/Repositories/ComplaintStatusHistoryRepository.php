<?php

namespace App\Repositories;

use App\Models\ComplaintStatusHistory;
use App\Repositories\Contracts\ComplaintStatusHistoryRepositoryInterface;

class ComplaintStatusHistoryRepository implements ComplaintStatusHistoryRepositoryInterface
{
    public function create(array $attributes): ComplaintStatusHistory
    {
        return ComplaintStatusHistory::query()->create($attributes);
    }
}
