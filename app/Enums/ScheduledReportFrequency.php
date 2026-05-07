<?php

namespace App\Enums;

enum ScheduledReportFrequency: string
{
    case Weekly = 'weekly'; // weekly frequency
    case Monthly = 'monthly'; // monthly frequency
}
