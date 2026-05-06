<?php

namespace App\Services;

use App\Enums\AttachmentVisibility;
use App\Enums\ReplyVisibility;
use App\Enums\TicketStatus;
use App\Enums\UserType;
use App\Events\TicketReplied;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Repositories\Contracts\TicketReplyRepositoryInterface;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class TicketReplyService
{
    public function __construct(
        private TicketReplyRepositoryInterface $replies,
        private AttachmentService $attachments,
        private TicketService $tickets,
        private SlaTrackingService $slaTracking,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function addReply(Ticket $ticket, User $user, string $body, ReplyVisibility $visibility = ReplyVisibility::Public, array $files = []): TicketReply
    {
        if ($ticket->isClosed()) {
            throw new InvalidArgumentException('Closed tickets cannot receive replies.');
        }

        $attachmentVisibility = $visibility === ReplyVisibility::Internal ? AttachmentVisibility::Internal : AttachmentVisibility::Public;
        $this->attachments->ensureFilesAllowed($ticket, $files, $attachmentVisibility);

        $reply = $this->replies->create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => $body,
            'visibility' => $visibility,
        ]);

        foreach ($files as $file) {
            $this->attachments->storeFor($reply, $user, $file, $attachmentVisibility);
        }

        if ($visibility === ReplyVisibility::Public) {
            if ($user->user_type !== UserType::Customer) {
                $this->slaTracking->markFirstResponse($ticket);
            }

            $this->applyWorkflowAfterReply($ticket, $user);
            TicketReplied::dispatch($ticket->refresh()->load(['customer', 'assignedAgent', 'company']), $reply, $user);
        }

        activity()->performedOn($reply)->causedBy($user)->event('ticket.reply.created')->log('Ticket reply created');

        return $reply->load('attachments');
    }

    private function applyWorkflowAfterReply(Ticket $ticket, User $user): void
    {
        if ($user->user_type === UserType::Customer && $ticket->status === TicketStatus::WaitingCustomer) {
            $this->tickets->changeStatus($ticket, $user, TicketStatus::InProgress, 'Customer replied');

            return;
        }

        if ($user->user_type !== UserType::Customer && in_array($ticket->status, [TicketStatus::Open, TicketStatus::InProgress], true)) {
            $this->tickets->changeStatus($ticket, $user, TicketStatus::WaitingCustomer, 'Support replied');
        }
    }
}
