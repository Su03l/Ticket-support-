<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;

class InquiryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inquiries.view') || $user->can('inquiries.view.own') || $user->can('inquiries.reply');
    }

    public function view(User $user, Inquiry $inquiry): bool
    {
        if ($user->can('inquiries.view') && ($user->company_id === null || $user->company_id === $inquiry->company_id)) {
            return true;
        }

        if ($user->can('inquiries.view.own') && $inquiry->customer_id === $user->id) {
            return true;
        }

        return $user->can('inquiries.reply')
            && $inquiry->company_id === $user->company_id
            && ($inquiry->department_id === $user->department_id || $inquiry->assigned_to_id === $user->id);
    }

    public function create(User $user): bool
    {
        return $user->can('inquiries.create') && $user->company_id !== null;
    }

    public function reply(User $user, Inquiry $inquiry): bool
    {
        return $user->can('inquiries.reply') && $this->view($user, $inquiry) && ! $inquiry->isClosed();
    }

    public function close(User $user, Inquiry $inquiry): bool
    {
        return $user->can('inquiries.close') && $this->view($user, $inquiry);
    }

    public function convert(User $user, Inquiry $inquiry): bool
    {
        return $user->can('tickets.create') && $this->reply($user, $inquiry) && $inquiry->converted_ticket_id === null;
    }

    public function viewInternal(User $user, Inquiry $inquiry): bool
    {
        return $this->view($user, $inquiry) && ($user->can('inquiries.view') || $user->can('inquiries.reply'));
    }
}
