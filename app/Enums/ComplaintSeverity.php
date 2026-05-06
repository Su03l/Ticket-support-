<?php

namespace App\Enums;

enum ComplaintSeverity: string
{
    case Low = 'low'; // low severity complaint
    case Medium = 'medium'; // medium severity complaint
    case High = 'high'; // high severity complaint
    case Critical = 'critical'; // critical severity complaint
}
