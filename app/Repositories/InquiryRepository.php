<?php

namespace App\Repositories;

use App\Models\Inquiry;
use App\Models\User;
use App\Repositories\Contracts\InquiryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class InquiryRepository implements InquiryRepositoryInterface
{
    public function paginatedForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Inquiry::query()
            ->with(['department:id,name', 'customer:id,name,email', 'assignedAgent:id,name,email', 'convertedTicket:id,ticket_number'])
            ->tap(fn ($query) => $this->applyVisibility($query, $user))
            ->when(($filters['status'] ?? null), fn ($query, $status) => $query->where('status', $status))
            ->when(($filters['department_id'] ?? null), fn ($query, $departmentId) => $query->where('department_id', $departmentId))
            ->when(($filters['search'] ?? null), function ($query, string $search): void {
                $query->where(fn ($query) => $query->where('inquiry_number', 'like', "%{$search}%")->orWhere('subject', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $attributes): Inquiry
    {
        return Inquiry::query()->create($attributes);
    }

    public function update(Inquiry $inquiry, array $attributes): Inquiry
    {
        $inquiry->forceFill($attributes)->save();

        return $inquiry->refresh();
    }

    public function findVisibleForUser(User $user, int $id): ?Inquiry
    {
        return Inquiry::query()
            ->with([
                'department:id,name',
                'customer:id,name,email,user_type',
                'assignedAgent:id,name,email',
                'convertedTicket:id,ticket_number,title',
                'replies.user:id,name,email,user_type',
                'replies.attachments',
                'statusHistories.changedBy:id,name,email',
                'slaRecord',
            ])
            ->whereKey($id)
            ->tap(fn ($query) => $this->applyVisibility($query, $user))
            ->first();
    }

    private function applyVisibility($query, User $user): void
    {
        if ($user->can('inquiries.view') && $user->company_id === null) {
            return;
        }

        if ($user->can('inquiries.view') && $user->company_id !== null) {
            $query->where('company_id', $user->company_id);

            return;
        }

        $query->where(function ($query) use ($user): void {
            if ($user->can('inquiries.view.own')) {
                $query->orWhere('customer_id', $user->id);
            }

            if ($user->can('inquiries.reply') && $user->department_id !== null) {
                $query->orWhere('department_id', $user->department_id)->orWhere('assigned_to_id', $user->id);
            }
        });
    }
}
