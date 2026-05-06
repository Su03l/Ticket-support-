<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('tickets.view')
            || $user->can('tickets.view.own')
            || $user->can('tickets.view.department')
            || $user->can('tickets.view.assigned');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->can('tickets.view') && ($user->company_id === null || $user->company_id === $ticket->company_id)) {
            return true;
        }

        if ($user->can('tickets.view.own') && $ticket->customer_id === $user->id) {
            return true;
        }

        if ($user->can('tickets.view.department') && $ticket->company_id === $user->company_id && $ticket->department_id === $user->department_id) {
            return true;
        }

        return $user->can('tickets.view.assigned')
            && $ticket->company_id === $user->company_id
            && $ticket->assigned_to_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('tickets.create') && $user->company_id !== null;
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.reply') && $this->view($user, $ticket) && ! $ticket->isClosed();
    }

    public function comment(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.comment') && $this->view($user, $ticket);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        if (! $user->can('tickets.assign') || ! $this->view($user, $ticket)) {
            return false;
        }

        if ($user->can('tickets.view')) {
            return true;
        }

        return $user->can('tickets.view.department') && $user->department_id === $ticket->department_id;
    }

    public function transfer(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.transfer') && $this->view($user, $ticket);
    }

    public function close(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.close') && $this->view($user, $ticket);
    }

    public function reopen(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.reopen') && $this->view($user, $ticket);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.delete') && $this->view($user, $ticket);
    }

    public function viewHistory(User $user, Ticket $ticket): bool
    {
        return $user->can('tickets.assign') || $user->can('tickets.transfer') || $user->can('tickets.view.department') || $user->can('tickets.view');
    }
}
