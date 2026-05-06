<?php

namespace App\Repositories;

use App\Models\Complaint;
use App\Models\User;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ComplaintRepository implements ComplaintRepositoryInterface
{
    public function paginatedForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Complaint::query()
            ->with(['department:id,name', 'customer:id,name,email', 'assignedAgent:id,name,email', 'relatedTicket:id,ticket_number'])
            ->tap(fn ($query) => $this->applyVisibility($query, $user))
            ->when(($filters['status'] ?? null), fn ($query, $status) => $query->where('status', $status))
            ->when(($filters['severity'] ?? null), fn ($query, $severity) => $query->where('severity', $severity))
            ->when(($filters['department_id'] ?? null), fn ($query, $departmentId) => $query->where('department_id', $departmentId))
            ->when(($filters['search'] ?? null), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('complaint_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $attributes): Complaint
    {
        return Complaint::query()->create($attributes);
    }

    public function update(Complaint $complaint, array $attributes): Complaint
    {
        $complaint->forceFill($attributes)->save();

        return $complaint->refresh();
    }

    public function findVisibleForUser(User $user, int $id): ?Complaint
    {
        return Complaint::query()
            ->with([
                'department:id,name',
                'customer:id,name,email,user_type',
                'assignedAgent:id,name,email',
                'relatedTicket:id,ticket_number,title',
                'replies.user:id,name,email,user_type',
                'replies.attachments',
                'statusHistories.changedBy:id,name,email',
            ])
            ->whereKey($id)
            ->tap(fn ($query) => $this->applyVisibility($query, $user))
            ->first();
    }

    private function applyVisibility($query, User $user): void
    {
        if ($user->can('complaints.view') && $user->company_id === null) {
            return;
        }

        if ($user->can('complaints.view') && $user->company_id !== null) {
            $query->where('company_id', $user->company_id);

            return;
        }

        $query->where(function ($query) use ($user): void {
            if ($user->can('complaints.view.own')) {
                $query->orWhere('customer_id', $user->id);
            }

            if ($user->can('complaints.view.department') && $user->department_id !== null) {
                $query->orWhere('department_id', $user->department_id);
            }

            if ($user->can('complaints.assign')) {
                $query->orWhere('assigned_to_id', $user->id);
            }
        });
    }
}
