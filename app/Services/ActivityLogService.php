<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Activitylog\Models\Activity;

class ActivityLogService
{
    public function logsForUser(User $user, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Activity::query()
            ->with('causer')
            ->when($user->company_id !== null, fn ($query) => $query->where(function ($query) use ($user): void {
                $query->where('properties->company_id', $user->company_id)
                    ->orWhereHasMorph('subject', '*', fn ($query) => $query->where('company_id', $user->company_id));
            }))
            ->when(($filters['module'] ?? null), fn ($query, $module) => $query->where('subject_type', 'like', "%{$module}%"))
            ->when(($filters['event'] ?? null), fn ($query, $event) => $query->where('event', $event))
            ->when(($filters['user_id'] ?? null), fn ($query, $userId) => $query->where('causer_id', $userId))
            ->when(($filters['date_from'] ?? null), fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when(($filters['date_to'] ?? null), fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate($perPage);
    }
}
