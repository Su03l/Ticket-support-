<?php

namespace App\Services;

use App\Enums\MailboxMessageType;
use App\Events\MailboxMessageCreated;
use App\Models\MailboxMessage;
use App\Models\User;
use App\Repositories\Contracts\MailboxRepositoryInterface;

class MailboxService
{
    public function __construct(
        private MailboxRepositoryInterface $mailbox,
    ) {}

    public function send(
        User $recipient,
        string $subject,
        string $body,
        ?User $sender = null,
        ?MailboxMessageType $type = null,
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?int $companyId = null,
    ): MailboxMessage {
        $message = $this->mailbox->create([
            'company_id' => $companyId ?? $recipient->company_id,
            'sender_id' => $sender?->id,
            'recipient_id' => $recipient->id,
            'subject' => $subject,
            'body' => $body,
            'type' => $type,
            'related_type' => $relatedType,
            'related_id' => $relatedId,
        ]);

        broadcast(new MailboxMessageCreated($message))->toOthers();

        return $message;
    }

    public function markAsRead(User $recipient, MailboxMessage $message): MailboxMessage
    {
        $this->ensureRecipient($recipient, $message);

        if ($message->read_at === null) {
            $message->forceFill(['read_at' => now()])->save();
        }

        return $message->refresh();
    }

    public function markAsUnread(User $recipient, MailboxMessage $message): MailboxMessage
    {
        $this->ensureRecipient($recipient, $message);

        $message->forceFill(['read_at' => null])->save();

        return $message->refresh();
    }

    public function archive(User $recipient, MailboxMessage $message): MailboxMessage
    {
        return $this->mailbox->archiveForRecipient($recipient, $message);
    }

    private function ensureRecipient(User $recipient, MailboxMessage $message): void
    {
        if ($message->recipient_id !== $recipient->id) {
            abort(403);
        }
    }
}
