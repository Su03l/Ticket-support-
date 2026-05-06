<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\ReportRepositoryInterface;

class ReportService
{
    public function __construct(private ReportRepositoryInterface $reports) {}

    public function dashboard(User $user, array $filters = []): array
    {
        return $this->reports->summaryForUser($user, $filters);
    }
}
