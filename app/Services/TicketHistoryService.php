<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketTransfer;
use App\Models\User;
use App\Repositories\Contracts\TicketAssignmentRepositoryInterface;
use App\Repositories\Contracts\TicketTransferRepositoryInterface;
use InvalidArgumentException;

class TicketHistoryService
{
    public function __construct(
        private TicketAssignmentRepositoryInterface $assignments,
        private TicketTransferRepositoryInterface $transfers,
    ) {}

    public function recordAssignment(Ticket $ticket, User $assignedBy, User $assignedTo, ?User $fromUser = null, ?string $note = null): TicketAssignment
    {
        return $this->assignments->create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'assigned_by_id' => $assignedBy->id,
            'assigned_to_id' => $assignedTo->id,
            'from_user_id' => $fromUser?->id,
            'note' => $note,
        ]);
    }

    public function recordTransfer(Ticket $ticket, User $transferredBy, Department $fromDepartment, Department $toDepartment, ?string $reason = null): TicketTransfer
    {
        if ($fromDepartment->company_id !== $ticket->company_id || $toDepartment->company_id !== $ticket->company_id) {
            throw new InvalidArgumentException('Ticket transfers must stay inside the same company.');
        }

        return $this->transfers->create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'transferred_by_id' => $transferredBy->id,
            'from_department_id' => $fromDepartment->id,
            'to_department_id' => $toDepartment->id,
            'reason' => $reason,
        ]);
    }
}
