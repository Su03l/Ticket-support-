<?php

namespace App\Services;

use App\Enums\AttachmentVisibility;
use App\Enums\ReplyVisibility;
use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\User;
use App\Repositories\Contracts\ComplaintReplyRepositoryInterface;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class ComplaintReplyService
{
    public function __construct(
        private ComplaintReplyRepositoryInterface $replies,
        private AttachmentService $attachments,
        private SlaTrackingService $slaTracking,
    ) {}

    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function addReply(Complaint $complaint, User $user, string $body, ReplyVisibility $visibility = ReplyVisibility::Public, array $files = []): ComplaintReply
    {
        if ($complaint->isClosed()) {
            throw new InvalidArgumentException('Closed complaints cannot receive replies.');
        }

        $attachmentVisibility = $visibility === ReplyVisibility::Internal ? AttachmentVisibility::Internal : AttachmentVisibility::Public;
        $this->attachments->ensureFilesAllowed($complaint, $files, $attachmentVisibility);

        $reply = $this->replies->create([
            'company_id' => $complaint->company_id,
            'complaint_id' => $complaint->id,
            'user_id' => $user->id,
            'body' => $body,
            'visibility' => $visibility,
        ]);

        foreach ($files as $file) {
            $this->attachments->storeFor($reply, $user, $file, $attachmentVisibility);
        }

        if ($visibility === ReplyVisibility::Public && $user->id !== $complaint->customer_id) {
            $this->slaTracking->markFirstResponse($complaint);
        }

        activity()->performedOn($reply)->causedBy($user)->event('complaint.reply.created')->log('Complaint reply created');

        return $reply->load('attachments');
    }
}
