<?php

namespace App\Services;

use App\Enums\InquiryStatus;
use App\Enums\TicketSource;
use App\Events\InquiryAssigned;
use App\Events\InquiryConvertedToTicket;
use App\Events\InquiryCreated;
use App\Models\Department;
use App\Models\Inquiry;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\InquiryRepositoryInterface;
use App\Repositories\Contracts\InquiryStatusHistoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InquiryService
{
    public function __construct(
        private InquiryRepositoryInterface $inquiries,
        private InquiryStatusHistoryRepositoryInterface $statusHistories,
        private InquiryNumberGenerator $numbers,
        private TicketService $tickets,
        private SlaTrackingService $slaTracking,
    ) {}

    public function listInquiriesForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->inquiries->paginatedForUser($user, $filters, $perPage);
    }

    public function createInquiry(User $customer, array $attributes, ?User $actor = null): Inquiry
    {
        $actor ??= $customer;

        if ($customer->company_id === null) {
            throw new InvalidArgumentException('Inquiries must belong to a company.');
        }

        $departmentId = $this->resolveDepartmentId($customer, $attributes['department_id'] ?? null);

        return DB::transaction(function () use ($customer, $actor, $attributes, $departmentId): Inquiry {
            $inquiry = $this->inquiries->create([
                'company_id' => $customer->company_id,
                'department_id' => $departmentId,
                'customer_id' => $customer->id,
                'inquiry_number' => $this->numbers->generate(),
                'subject' => $attributes['subject'],
                'body' => $attributes['body'],
                'status' => InquiryStatus::New,
            ]);

            $this->recordStatus($inquiry, $actor, null, InquiryStatus::New, 'Inquiry created');
            $this->slaTracking->attachTo($inquiry);
            activity()->performedOn($inquiry)->causedBy($actor)->event('inquiry.created')->log('Inquiry created');
            InquiryCreated::dispatch($inquiry->load(['company', 'department', 'customer']), $actor);

            return $inquiry;
        });
    }

    public function viewInquiry(User $user, int $id): Inquiry
    {
        return $this->inquiries->findVisibleForUser($user, $id) ?? abort(404);
    }

    public function assignInquiry(Inquiry $inquiry, User $actor, User $assignee): Inquiry
    {
        $this->ensureSameCompany($inquiry, $assignee);

        $inquiry = $this->inquiries->update($inquiry, [
            'assigned_to_id' => $assignee->id,
            'status' => $inquiry->status === InquiryStatus::New ? InquiryStatus::Open : $inquiry->status,
        ]);

        activity()->performedOn($inquiry)->causedBy($actor)->event('inquiry.assigned')->log('Inquiry assigned');
        InquiryAssigned::dispatch($inquiry->load('company'), $actor, $assignee);

        return $inquiry;
    }

    public function changeStatus(Inquiry $inquiry, User $actor, InquiryStatus $status, ?string $reason = null): Inquiry
    {
        $oldStatus = $inquiry->status;
        $inquiry = $this->inquiries->update($inquiry, [
            'status' => $status,
            'closed_at' => in_array($status, [InquiryStatus::Closed, InquiryStatus::Cancelled], true) ? now() : $inquiry->closed_at,
        ]);

        $this->recordStatus($inquiry, $actor, $oldStatus, $status, $reason);
        activity()->performedOn($inquiry)->causedBy($actor)->event('inquiry.status_changed')->log('Inquiry status changed');

        return $inquiry;
    }

    public function convertToTicket(Inquiry $inquiry, User $actor, ?int $priorityId = null): Ticket
    {
        if ($inquiry->department_id === null) {
            throw new InvalidArgumentException('Inquiry must have a department before conversion.');
        }

        return DB::transaction(function () use ($inquiry, $actor, $priorityId): Ticket {
            $ticket = $this->tickets->createTicket($inquiry->customer, [
                'department_id' => $inquiry->department_id,
                'priority_id' => $priorityId,
                'title' => $inquiry->subject,
                'description' => $inquiry->body,
                'source' => TicketSource::Internal,
            ], $actor);

            $oldStatus = $inquiry->status;
            $inquiry = $this->inquiries->update($inquiry, [
                'converted_ticket_id' => $ticket->id,
                'status' => InquiryStatus::ConvertedToTicket,
                'closed_at' => now(),
            ]);

            $this->recordStatus($inquiry, $actor, $oldStatus, InquiryStatus::ConvertedToTicket, 'Converted to ticket');
            activity()->performedOn($inquiry)->causedBy($actor)->event('inquiry.converted')->log('Inquiry converted to ticket');
            InquiryConvertedToTicket::dispatch($inquiry, $ticket, $actor);

            return $ticket;
        });
    }

    private function recordStatus(Inquiry $inquiry, User $actor, ?InquiryStatus $oldStatus, InquiryStatus $newStatus, ?string $reason = null): void
    {
        $this->statusHistories->create([
            'company_id' => $inquiry->company_id,
            'inquiry_id' => $inquiry->id,
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

        return Department::query()->where('company_id', $customer->company_id)->findOrFail($departmentId)->id;
    }

    private function ensureSameCompany(Inquiry $inquiry, User $user): void
    {
        if ($inquiry->company_id !== $user->company_id) {
            throw new InvalidArgumentException('Inquiry users must belong to the same company.');
        }
    }
}
