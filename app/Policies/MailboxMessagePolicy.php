<?php

namespace App\Policies;

use App\Models\MailboxMessage;
use App\Models\User;

class MailboxMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('mailbox.view');
    }

    public function view(User $user, MailboxMessage $message): bool
    {
        return $user->can('mailbox.view')
            && $message->recipient_id === $user->id
            && ($message->company_id === null || $message->company_id === $user->company_id);
    }

    public function read(User $user, MailboxMessage $message): bool
    {
        return $user->can('mailbox.read') && $this->view($user, $message);
    }

    public function send(User $user): bool
    {
        return $user->can('mailbox.send');
    }

    public function archive(User $user, MailboxMessage $message): bool
    {
        return $user->can('mailbox.archive') && $this->view($user, $message);
    }

    public function delete(User $user, MailboxMessage $message): bool
    {
        return $user->can('mailbox.delete') && $this->view($user, $message);
    }
}
