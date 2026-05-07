<?php

namespace App\Enums;

enum SlaStatus: string
{
    case Active = 'active'; // sla is active
    case Met = 'met'; // sla is met
    case Breached = 'breached'; // sla is breached
    case Cancelled = 'cancelled'; // sla is cancelled
}
