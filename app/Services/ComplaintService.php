<?php

namespace App\Services;

use App\Enums\ComplaintSeverity;
use App\Enums\ComplaintStatus;
use App\Events\ComplaintCreated;
use App\Events\ComplaintEscalated;
use App\Events\ComplaintStatusChanged;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\ComplaintRepositoryInterface;
use App\Repositories\Contracts\ComplaintStatusHistoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ComplaintService
{
    public function __construct(
        private ComplaintRepositoryInterface $complaints,
        private ComplaintStatusHistoryRepositoryInterface $statusHistories,
        private ComplaintNumberGenerator $complaintNumbers,
        private SlaTrackingService $slaTracking,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listComplaintsForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->complaints->paginatedForUser($user, $filters, $perPage);
    }

    /**
     * @param  array{title: string, description: string, severity: ComplaintSeverity|string, department_id?: int|null, related_ticket_id?: int|null}  $attributes
     */
    public function createComplaint(User $customer, array $attributes, ?User $actor = null): Complaint
    {
        $actor ??= $customer;

        if ($customer->company_id === null) {
            throw new InvalidArgumentException('Complaints must belong to a company.');
        }

        $departmentId = $this->resolveDepartmentId($customer, $attributes['department_id'] ?? null);
        $relatedTicketId = $this->resolveRelatedTicketId($customer, $attributes['related_ticket_id'] ?? null);
        $severity = $attributes['severity'] instanceof ComplaintSeverity ? $attributes['severity'] : ComplaintSeverity::from($attributes['severity']);

        return DB::transaction(function () use ($customer, $actor, $attributes, $departmentId, $relatedTicketId, $severity): Complaint {
            $complaint = $this->complaints->create([
                'company_id' => $customer->company_id,
                'department_id' => $departmentId,
                'customer_id' => $customer->id,
                'related_ticket_id' => $relatedTicketId,
                'complaint_number' => $this->complaintNumbers->generate(),
                'title' => $attributes['title'],
                'description' => $attributes['description'],
                'severity' => $severity,
                'status' => ComplaintStatus::New,
            ]);

            $this->recordStatus($complaint, $actor, null, ComplaintStatus::New, 'Complaint created');
            $this->slaTracking->attachTo($complaint);
            activity()->performedOn($complaint)->causedBy($actor)->event('complaint.created')->log('Complaint created');
            ComplaintCreated::dispatch($complaint->load(['company', 'department', 'customer']), $actor);

            return $complaint;
        });
    }

    public function viewComplaint(User $user, int $complaintId): Complaint
    {
        return $this->complaints->findVisibleForUser($user, $complaintId) ?? abort(404);
    }

    public function assignComplaint(Complaint $complaint, User $actor, User $assignee): Complaint
    {
        $this->ensureSameCompany($complaint, $assignee);

        if ($complaint->department_id !== null && $assignee->department_id !== null && $assignee->department_id !== $complaint->department_id) {
            throw new InvalidArgumentException('Complaint assignee must belong to the complaint department.');
        }

        $complaint = $this->complaints->update($complaint, [
            'assigned_to_id' => $assignee->id,
            'status' => $complaint->status === ComplaintStatus::New ? ComplaintStatus::UnderReview : $complaint->status,
        ]);

        activity()->performedOn($complaint)->causedBy($actor)->event('complaint.assigned')->log('Complaint assigned');

        return $complaint;
    }

    public function escalateComplaint(Complaint $complaint, User $actor, ?string $reason = null): Complaint
    {
        $complaint = $this->changeStatus($complaint, $actor, ComplaintStatus::Escalated, $reason ?: 'Complaint escalated');
        ComplaintEscalated::dispatch($complaint->load('company'), $actor, $reason);

        return $complaint;
    }

    public function changeStatus(Complaint $complaint, User $actor, ComplaintStatus $status, ?string $reason = null): Complaint
    {
        $oldStatus = $complaint->status;

        $timestamps = match ($status) {
            ComplaintStatus::Resolved => ['resolved_at' => now()],
            ComplaintStatus::Closed => ['closed_at' => now()],
            default => [],
        };

        $complaint = $this->complaints->update($complaint, [
            'status' => $status,
            ...$timestamps,
        ]);

        $this->recordStatus($complaint, $actor, $oldStatus, $status, $reason);
        if (in_array($status, [ComplaintStatus::Resolved, ComplaintStatus::Closed], true)) {
            $this->slaTracking->markResolved($complaint);
        }
        activity()->performedOn($complaint)->causedBy($actor)->event('complaint.status_changed')->log('Complaint status changed');
        ComplaintStatusChanged::dispatch($complaint->load(['company', 'customer']), $actor, $oldStatus, $status);

        return $complaint;
    }

    private function recordStatus(Complaint $complaint, User $actor, ?ComplaintStatus $oldStatus, ComplaintStatus $newStatus, ?string $reason = null): void
    {
        $this->statusHistories->create([
            'company_id' => $complaint->company_id,
            'complaint_id' => $complaint->id,
            'changed_by_id' => $actor->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
        ]);
    }

    private function resolveDepartmentId(User $customer, ?int $departmentId): ?int
    {
        if ($departmentId === null) {
            return null;
        }

        return Department::query()
            ->where('company_id', $customer->company_id)
            ->findOrFail($departmentId)
            ->id;
    }

    private function resolveRelatedTicketId(User $customer, ?int $ticketId): ?int
    {
        if ($ticketId === null) {
            return null;
        }

        return Ticket::query()
            ->where('company_id', $customer->company_id)
            ->where('customer_id', $customer->id)
            ->findOrFail($ticketId)
            ->id;
    }

    private function ensureSameCompany(Complaint $complaint, User $user): void
    {
        if ($complaint->company_id !== $user->company_id) {
            throw new InvalidArgumentException('Complaint users must belong to the same company.');
        }
    }
}
