<?php

namespace App\Events;

use App\Models\MailboxMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MailboxMessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public MailboxMessage $message,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('mailbox.users.'.$this->message->recipient_id);
    }

    public function broadcastAs(): string
    {
        return 'mailbox.message.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'recipient_id' => $this->message->recipient_id,
            'company_id' => $this->message->company_id,
            'type' => $this->message->type?->value,
            'subject' => $this->message->subject,
            'unread' => true,
        ];
    }
}
