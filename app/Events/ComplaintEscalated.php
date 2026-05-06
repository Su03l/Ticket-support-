<?php

namespace App\Events;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplaintEscalated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Complaint $complaint,
        public User $actor,
        public ?string $reason = null,
    ) {}
}
