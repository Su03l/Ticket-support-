<?php

namespace App\Enums;

enum EscalationStatus: string
{
    case Open = 'open'; // open escalation status
    case Acknowledged = 'acknowledged'; // acknowledged escalation status
    case Resolved = 'resolved'; // resolved escalation status
    case Cancelled = 'cancelled'; // cancelled escalation status
}
