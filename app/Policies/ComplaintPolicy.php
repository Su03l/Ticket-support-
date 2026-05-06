<?php

namespace App\Policies;

use App\Models\Complaint;
use App\Models\User;

class ComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('complaints.view')
            || $user->can('complaints.view.own')
            || $user->can('complaints.view.department');
    }

    public function view(User $user, Complaint $complaint): bool
    {
        if ($user->can('complaints.view') && ($user->company_id === null || $user->company_id === $complaint->company_id)) {
            return true;
        }

        if ($user->can('complaints.view.own') && $complaint->customer_id === $user->id) {
            return true;
        }

        return $user->can('complaints.view.department')
            && $complaint->company_id === $user->company_id
            && $complaint->department_id === $user->department_id;
    }

    public function create(User $user): bool
    {
        return $user->can('complaints.create') && $user->company_id !== null;
    }

    public function reply(User $user, Complaint $complaint): bool
    {
        return $user->can('complaints.reply') && $this->view($user, $complaint) && ! $complaint->isClosed();
    }

    public function assign(User $user, Complaint $complaint): bool
    {
        return $user->can('complaints.assign') && $this->view($user, $complaint);
    }

    public function close(User $user, Complaint $complaint): bool
    {
        return $user->can('complaints.close') && $this->view($user, $complaint);
    }

    public function delete(User $user, Complaint $complaint): bool
    {
        return $user->can('complaints.delete') && $this->view($user, $complaint);
    }

    public function viewInternal(User $user, Complaint $complaint): bool
    {
        return $this->view($user, $complaint) && ($user->can('complaints.view') || $user->can('complaints.view.department') || $user->can('complaints.assign'));
    }
}
