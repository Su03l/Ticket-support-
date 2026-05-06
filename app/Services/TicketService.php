<?php

namespace App\Services;

use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use App\Events\TicketAssigned;
use App\Events\TicketCreated;
use App\Events\TicketStatusChanged;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TicketService
{
    public function __construct(
        private TicketRepositoryInterface $tickets,
        private TicketNumberGenerator $ticketNumbers,
        private TicketStatusTransition $statusTransition,
        private TicketHistoryService $history,
        private StatusHistoryService $statusHistory,
        private NotificationService $notifications,
        private SlaTrackingService $slaTracking,
        private CustomerSatisfactionService $satisfaction,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function listTicketsForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->tickets->paginatedForUser($user, $filters, $perPage);
    }

    /**
     * @param  array{department_id: int, title: string, description: string, category_id?: int|null, priority_id?: int|null, source?: TicketSource|string|null}  $attributes
     */
    public function createTicket(User $customer, array $attributes, ?User $actor = null): Ticket
    {
        $actor ??= $customer;

        if ($customer->company_id === null) {
            throw new InvalidArgumentException('Tickets must belong to a company.');
        }

        $department = Department::query()
            ->where('company_id', $customer->company_id)
            ->findOrFail($attributes['department_id']);

        return DB::transaction(function () use ($customer, $actor, $attributes, $department): Ticket {
            $ticket = $this->tickets->create([
                'company_id' => $customer->company_id,
                'department_id' => $department->id,
                'customer_id' => $customer->id,
                'category_id' => $attributes['category_id'] ?? null,
                'priority_id' => $attributes['priority_id'] ?? null,
                'ticket_number' => $this->ticketNumbers->generate(),
                'title' => $attributes['title'],
                'description' => $attributes['description'],
                'status' => TicketStatus::New,
                'source' => $attributes['source'] ?? TicketSource::Web,
            ]);

            $this->statusHistory->record($ticket, $actor, null, TicketStatus::New, 'Ticket created');
            $this->slaTracking->attachTo($ticket);
            activity()->performedOn($ticket)->causedBy($actor)->event('ticket.created')->log('Ticket created');
            TicketCreated::dispatch($ticket->load(['customer', 'company']), $actor);

            return $ticket;
        });
    }

    public function viewTicket(User $user, int $ticketId): Ticket
    {
        return $this->tickets->findVisibleForUser($user, $ticketId) ?? abort(404);
    }

    public function assignTicket(Ticket $ticket, User $assignedBy, User $assignee, ?string $note = null): Ticket
    {
        $this->ensureSameCompany($ticket, $assignee);

        if ($assignee->department_id !== null && $assignee->department_id !== $ticket->department_id) {
            throw new InvalidArgumentException('Assigned agent must belong to the ticket department.');
        }

        $fromUser = $ticket->assignedAgent;

        $ticket = $this->tickets->update($ticket, [
            'assigned_to_id' => $assignee->id,
            'status' => $ticket->status === TicketStatus::New ? TicketStatus::Open : $ticket->status,
        ]);

        $this->history->recordAssignment($ticket, $assignedBy, $assignee, $fromUser, $note);
        activity()->performedOn($ticket)->causedBy($assignedBy)->event('ticket.assigned')->log('Ticket assigned');
        TicketAssigned::dispatch($ticket->load(['company']), $assignedBy, $assignee);

        return $ticket;
    }

    public function transferTicket(Ticket $ticket, User $transferredBy, Department $toDepartment, ?string $reason = null): Ticket
    {
        if (blank($reason)) {
            throw new InvalidArgumentException('A transfer reason is required.');
        }

        if ($toDepartment->company_id !== $ticket->company_id) {
            throw new InvalidArgumentException('Transfer department must belong to the ticket company.');
        }

        $fromDepartment = $ticket->department;
        $oldStatus = $ticket->status;

        $ticket = $this->tickets->update($ticket, [
            'department_id' => $toDepartment->id,
            'assigned_to_id' => null,
            'status' => TicketStatus::WaitingDepartment,
        ]);

        $this->history->recordTransfer($ticket, $transferredBy, $fromDepartment, $toDepartment, $reason);
        $this->statusHistory->record($ticket, $transferredBy, $oldStatus, TicketStatus::WaitingDepartment, $reason);
        if ($toDepartment->manager !== null) {
            $this->notifications->notify(
                recipient: $toDepartment->manager,
                type: 'ticket.transferred',
                title: 'Ticket transferred',
                body: "Ticket {$ticket->ticket_number} was transferred to {$toDepartment->name}.",
                link: route('tickets.show', $ticket),
                company: $ticket->company,
            );
        }
        activity()->performedOn($ticket)->causedBy($transferredBy)->event('ticket.transferred')->log('Ticket transferred');

        return $ticket;
    }

    public function changeStatus(Ticket $ticket, User $changedBy, TicketStatus $status, ?string $reason = null): Ticket
    {
        $oldStatus = $ticket->status;
        $this->statusTransition->ensureAllowed($oldStatus, $status, $reason);

        $timestamps = match ($status) {
            TicketStatus::Resolved => ['resolved_at' => now()],
            TicketStatus::Closed => ['closed_at' => now()],
            TicketStatus::Reopened => ['reopened_at' => now()],
            default => [],
        };

        $ticket = $this->tickets->update($ticket, [
            'status' => $status,
            ...$timestamps,
        ]);

        $this->statusHistory->record($ticket, $changedBy, $oldStatus, $status, $reason);
        if (in_array($status, [TicketStatus::Resolved, TicketStatus::Closed], true)) {
            $this->slaTracking->markResolved($ticket);
        }
        if ($status === TicketStatus::Closed) {
            $this->satisfaction->createForClosedTicket($ticket);
        }
        activity()->performedOn($ticket)->causedBy($changedBy)->event('ticket.status_changed')->log('Ticket status changed');
        TicketStatusChanged::dispatch($ticket, $changedBy, $oldStatus, $status);

        return $ticket;
    }

    public function closeTicket(Ticket $ticket, User $closedBy, ?string $reason = null): Ticket
    {
        return $this->changeStatus($ticket, $closedBy, TicketStatus::Closed, $reason);
    }

    public function reopenTicket(Ticket $ticket, User $reopenedBy, string $reason): Ticket
    {
        return $this->changeStatus($ticket, $reopenedBy, TicketStatus::Reopened, $reason);
    }

    private function ensureSameCompany(Ticket $ticket, User $user): void
    {
        if ($user->company_id !== $ticket->company_id) {
            throw new InvalidArgumentException('Ticket users must belong to the same company.');
        }
    }
}
