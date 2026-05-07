<?php

namespace App\Enums;

enum SlaAppliesTo: string
{
    case Tickets = 'tickets'; // tickets sla applies to
    case Complaints = 'complaints';
    case Inquiries = 'inquiries';
}
