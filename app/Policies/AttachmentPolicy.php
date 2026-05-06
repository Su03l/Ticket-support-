<?php

namespace App\Policies;

use App\Enums\AttachmentVisibility;
use App\Models\Attachment;
use App\Models\ComplaintReply;
use App\Models\InquiryReply;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketReply;
use App\Models\User;

class AttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('files.view') || $user->can('files.download');
    }

    public function view(User $user, Attachment $attachment): bool
    {
        if ($attachment->company_id !== $user->company_id) {
            return $user->company_id === null && $user->can('files.view');
        }

        if ($user->can('files.view') && $attachment->visibility === AttachmentVisibility::Public) {
            return true;
        }

        if ($user->can('files.view') && $attachment->visibility === AttachmentVisibility::Internal && $user->user_type->value !== 'customer') {
            return true;
        }

        $ticket = $this->ticketForAttachment($attachment);

        if ($ticket !== null) {
            if (! $user->can('view', $ticket)) {
                return false;
            }

            if ($attachment->visibility === AttachmentVisibility::Public) {
                return true;
            }

            return $user->can('tickets.comment') || $user->can('tickets.view.department') || $user->can('tickets.view.assigned') || $user->can('tickets.view');
        }

        $complaint = $attachment->attachable instanceof ComplaintReply ? $attachment->attachable->complaint : null;

        if ($complaint === null || ! $user->can('view', $complaint)) {
            $inquiry = $attachment->attachable instanceof InquiryReply ? $attachment->attachable->inquiry : null;

            if ($inquiry === null || ! $user->can('view', $inquiry)) {
                return false;
            }

            if ($attachment->visibility === AttachmentVisibility::Public) {
                return true;
            }

            return $user->can('inquiries.view') || $user->can('inquiries.reply');
        }

        if ($attachment->visibility === AttachmentVisibility::Public) {
            return true;
        }

        return $user->can('complaints.view.department') || $user->can('complaints.view') || $user->can('complaints.assign');
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        if (! $user->can('files.delete') || ! $this->view($user, $attachment)) {
            return false;
        }

        return $user->user_type->value !== 'customer' || $attachment->uploaded_by_id === $user->id;
    }

    public function download(User $user, Attachment $attachment): bool
    {
        return $user->can('files.download') && $this->view($user, $attachment);
    }

    private function ticketForAttachment(Attachment $attachment): ?Ticket
    {
        $attachable = $attachment->attachable;

        if ($attachable instanceof TicketReply || $attachable instanceof TicketComment) {
            return $attachable->ticket;
        }

        return null;
    }
}
