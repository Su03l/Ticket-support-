<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketTimeEntry;
use App\Models\User;

class TicketTimeTrackingService
{
    public function start(Ticket $ticket, User $user): TicketTimeEntry
    {
        $this->stopRunningEntries($ticket, $user);

        return TicketTimeEntry::query()->create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'started_at' => now(),
        ]);
    }

    public function stop(Ticket $ticket, User $user, ?string $note = null): ?TicketTimeEntry
    {
        $entry = $this->runningEntry($ticket, $user);

        if ($entry === null) {
            return null;
        }

        $stoppedAt = now();
        $entry->forceFill([
            'stopped_at' => $stoppedAt,
            'duration_seconds' => max(1, $entry->started_at->diffInSeconds($stoppedAt)),
            'note' => $note,
        ])->save();

        activity()->performedOn($ticket)->causedBy($user)->event('ticket.time_tracked')->log('Ticket time tracked');

        return $entry->refresh();
    }

    public function runningEntry(Ticket $ticket, User $user): ?TicketTimeEntry
    {
        return TicketTimeEntry::query()
            ->where('ticket_id', $ticket->id)
            ->where('user_id', $user->id)
            ->whereNull('stopped_at')
            ->latest('started_at')
            ->first();
    }

    public function secondsForTicket(Ticket $ticket, ?User $user = null): int
    {
        return (int) TicketTimeEntry::query()
            ->where('ticket_id', $ticket->id)
            ->when($user !== null, fn ($query) => $query->where('user_id', $user->id))
            ->sum('duration_seconds');
    }

    private function stopRunningEntries(Ticket $ticket, User $user): void
    {
        TicketTimeEntry::query()
            ->where('ticket_id', $ticket->id)
            ->where('user_id', $user->id)
            ->whereNull('stopped_at')
            ->get()
            ->each(fn (TicketTimeEntry $entry): ?TicketTimeEntry => $this->stop($ticket, $user));
    }
}
