<?php

namespace App\Services;

use App\Enums\TicketPresenceAction;
use App\Events\TicketPresenceUpdated;
use App\Models\Ticket;
use App\Models\TicketPresence;
use App\Models\User;
use Illuminate\Support\Collection;

class TicketPresenceService
{
    public function touch(Ticket $ticket, User $user, TicketPresenceAction $action): TicketPresence
    {
        $presence = TicketPresence::query()->updateOrCreate([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
        ], [
            'company_id' => $ticket->company_id,
            'action' => $action,
            'last_seen_at' => now(),
        ]);

        broadcast(new TicketPresenceUpdated($presence->load('user')))->toOthers();

        return $presence;
    }

    /**
     * @return Collection<int, TicketPresence>
     */
    public function activeColleagues(Ticket $ticket, User $user, int $freshForSeconds = 90): Collection
    {
        return TicketPresence::query()
            ->with('user:id,name,email')
            ->where('company_id', $ticket->company_id)
            ->where('ticket_id', $ticket->id)
            ->where('user_id', '!=', $user->id)
            ->where('last_seen_at', '>=', now()->subSeconds($freshForSeconds))
            ->latest('last_seen_at')
            ->get();
    }
}
