<?php

namespace App\Enums;

enum SlaStatus: string
{
    case Active = 'active'; // sla is active
    case Met = 'met';
    case Breached = 'breached';
    case Cancelled = 'cancelled';
}
