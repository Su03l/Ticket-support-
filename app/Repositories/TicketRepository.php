<?php

namespace App\Repositories;

use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TicketRepository implements TicketRepositoryInterface
{
    public function paginatedForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Ticket::query()
            ->with(['department:id,name', 'customer:id,name,email', 'assignedAgent:id,name,email', 'priority:id,name,color', 'category:id,name'])
            ->when(! $user->can('tickets.view'), fn ($query) => $this->applyVisibility($query, $user))
            ->when($user->can('tickets.view') && $user->company_id !== null, fn ($query) => $query->where('company_id', $user->company_id))
            ->when(($filters['status'] ?? null), fn ($query, $status) => $query->where('status', $status))
            ->when(($filters['priority_id'] ?? null), fn ($query, $priorityId) => $query->where('priority_id', $priorityId))
            ->when(($filters['department_id'] ?? null), fn ($query, $departmentId) => $query->where('department_id', $departmentId))
            ->when(($filters['assigned_to_id'] ?? null), fn ($query, $agentId) => $query->where('assigned_to_id', $agentId))
            ->when(($filters['search'] ?? null), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('ticket_number', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $attributes): Ticket
    {
        return Ticket::query()->create($attributes);
    }

    public function update(Ticket $ticket, array $attributes): Ticket
    {
        $ticket->forceFill($attributes)->save();

        return $ticket->refresh();
    }

    public function findVisibleForUser(User $user, int $id): ?Ticket
    {
        return Ticket::query()
            ->with([
                'department:id,name',
                'customer:id,name,email',
                'assignedAgent:id,name,email',
                'priority:id,name,color',
                'category:id,name',
                'replies.user:id,name,email',
                'replies.attachments',
                'comments.user:id,name,email',
                'comments.attachments',
                'assignments.assignedBy:id,name,email',
                'assignments.assignedTo:id,name,email',
                'assignments.fromUser:id,name,email',
                'transfers.transferredBy:id,name,email',
                'transfers.fromDepartment:id,name',
                'transfers.toDepartment:id,name',
                'statusHistories.changedBy:id,name,email',
            ])
            ->whereKey($id)
            ->tap(fn ($query) => $this->applyVisibility($query, $user))
            ->first();
    }

    private function applyVisibility($query, User $user): void
    {
        if ($user->can('tickets.view') && $user->company_id === null) {
            return;
        }

        if ($user->can('tickets.view') && $user->company_id !== null) {
            $query->where('company_id', $user->company_id);

            return;
        }

        $query->where(function ($query) use ($user): void {
            if ($user->can('tickets.view.own')) {
                $query->orWhere('customer_id', $user->id);
            }

            if ($user->can('tickets.view.department') && $user->department_id !== null) {
                $query->orWhere('department_id', $user->department_id);
            }

            if ($user->can('tickets.view.assigned')) {
                $query->orWhere('assigned_to_id', $user->id);
            }
        });
    }
}
