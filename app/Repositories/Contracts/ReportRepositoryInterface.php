<?php

namespace App\Repositories\Contracts;

use App\Models\User;

interface ReportRepositoryInterface
{
    public function summaryForUser(User $user, array $filters = []): array;
}
