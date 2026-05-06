<?php

namespace App\Events;

use App\Models\TicketPresence;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketPresenceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TicketPresence $presence,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('tickets.'.$this->presence->ticket_id);
    }

    public function broadcastAs(): string
    {
        return 'ticket.presence.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->presence->ticket_id,
            'user_id' => $this->presence->user_id,
            'user_name' => $this->presence->user?->name,
            'action' => $this->presence->action->value,
            'last_seen_at' => $this->presence->last_seen_at?->toIso8601String(),
        ];
    }
}
