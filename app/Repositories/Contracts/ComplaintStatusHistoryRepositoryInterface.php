<?php

namespace App\Repositories\Contracts;

use App\Models\ComplaintStatusHistory;

interface ComplaintStatusHistoryRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ComplaintStatusHistory;
}
