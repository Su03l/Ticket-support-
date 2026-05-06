<?php

namespace App\Enums;

enum TicketPresenceAction: string
{
    case Viewing = 'viewing';
    case Replying = 'replying';
    case Commenting = 'commenting';
}
