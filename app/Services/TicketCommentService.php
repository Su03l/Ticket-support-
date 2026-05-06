<?php

namespace App\Services;

use App\Enums\AttachmentVisibility;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Repositories\Contracts\TicketCommentRepositoryInterface;
use Illuminate\Http\UploadedFile;

class TicketCommentService
{
    public function __construct(
        private TicketCommentRepositoryInterface $comments,
        private AttachmentService $attachments,
        private TicketMentionService $mentions,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function addComment(Ticket $ticket, User $user, string $body, array $files = []): TicketComment
    {
        $this->attachments->ensureFilesAllowed($ticket, $files, AttachmentVisibility::Internal);

        $comment = $this->comments->create([
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => $body,
        ]);

        foreach ($files as $file) {
            $this->attachments->storeFor($comment, $user, $file, AttachmentVisibility::Internal);
        }

        $this->mentions->createMentionsForComment($ticket, $comment, $user);

        activity()->performedOn($comment)->causedBy($user)->event('ticket.comment.created')->log('Ticket comment created');

        return $comment->load('attachments');
    }
}
