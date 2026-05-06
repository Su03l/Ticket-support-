<?php

namespace App\Enums;

enum SlaStatus: string
{
    case Active = 'active';
    case Met = 'met';
    case Breached = 'breached';
    case Cancelled = 'cancelled';
}
