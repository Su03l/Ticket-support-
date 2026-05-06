<?php

namespace App\Services;

use App\Enums\AttachmentVisibility;
use App\Enums\InquiryStatus;
use App\Enums\ReplyVisibility;
use App\Enums\UserType;
use App\Events\InquiryAnswered;
use App\Models\Inquiry;
use App\Models\InquiryReply;
use App\Models\User;
use App\Repositories\Contracts\InquiryReplyRepositoryInterface;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class InquiryReplyService
{
    public function __construct(
        private InquiryReplyRepositoryInterface $replies,
        private AttachmentService $attachments,
        private InquiryService $inquiries,
        private SlaTrackingService $slaTracking,
    ) {}

    public function addReply(Inquiry $inquiry, User $user, string $body, ReplyVisibility $visibility = ReplyVisibility::Public, array $files = []): InquiryReply
    {
        if ($inquiry->isClosed()) {
            throw new InvalidArgumentException('Closed inquiries cannot receive replies.');
        }

        $attachmentVisibility = $visibility === ReplyVisibility::Internal ? AttachmentVisibility::Internal : AttachmentVisibility::Public;
        $this->attachments->ensureFilesAllowed($inquiry, $files, $attachmentVisibility);

        $reply = $this->replies->create([
            'company_id' => $inquiry->company_id,
            'inquiry_id' => $inquiry->id,
            'user_id' => $user->id,
            'body' => $body,
            'visibility' => $visibility,
        ]);

        foreach ($files as $file) {
            /** @var UploadedFile $file */
            $this->attachments->storeFor($reply, $user, $file, $attachmentVisibility);
        }

        if ($visibility === ReplyVisibility::Public && $user->user_type !== UserType::Customer) {
            $this->inquiries->changeStatus($inquiry, $user, InquiryStatus::Answered, 'Staff answered inquiry');
            $this->slaTracking->markFirstResponse($inquiry, now());
            InquiryAnswered::dispatch($inquiry->refresh()->load(['customer', 'company']), $reply, $user);
        } elseif ($visibility === ReplyVisibility::Public && $user->user_type === UserType::Customer && $inquiry->status === InquiryStatus::WaitingCustomer) {
            $this->inquiries->changeStatus($inquiry, $user, InquiryStatus::Open, 'Customer replied');
        }

        activity()->performedOn($reply)->causedBy($user)->event('inquiry.reply.created')->log('Inquiry reply created');

        return $reply->load('attachments');
    }
}
