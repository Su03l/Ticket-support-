<?php

namespace App\Livewire\Tickets;

use App\Models\Ticket;
use Livewire\Component;

class StatusHistory extends Component
{
    public Ticket $ticket;

    public function render()
    {
        $histories = $this->ticket->statusHistories()->with('changedBy')->latest()->get();

        return view('livewire.tickets.status-history', compact('histories'));
    }
}