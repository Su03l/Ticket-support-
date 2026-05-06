<?php

namespace App\Events;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InquiryCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Inquiry $inquiry, public User $actor) {}
}
