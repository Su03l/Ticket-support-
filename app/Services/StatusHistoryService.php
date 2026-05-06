<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use App\Repositories\Contracts\TicketStatusHistoryRepositoryInterface;

class StatusHistoryService
{
    public function __construct(
        private TicketStatusHistoryRepositoryInterface $statusHistories,
    ) {}

    public function record(Ticket $ticket, User $changedBy, ?TicketStatus $oldStatus, TicketStatus $newStatus, ?string $reason = null): TicketStatusHistory
    {
        return $this->statusHistories->create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'changed_by_id' => $changedBy->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'reason' => $reason,
        ]);
    }
}
