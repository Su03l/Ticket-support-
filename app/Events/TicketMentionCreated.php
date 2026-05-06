<?php

namespace App\Events;

use App\Models\TicketMention;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketMentionCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public TicketMention $mention,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('users.'.$this->mention->mentioned_user_id);
    }

    public function broadcastAs(): string
    {
        return 'ticket.mention.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->mention->id,
            'ticket_id' => $this->mention->ticket_id,
            'ticket_number' => $this->mention->ticket?->ticket_number,
            'mentioned_by' => $this->mention->mentionedBy?->name,
        ];
    }
}
