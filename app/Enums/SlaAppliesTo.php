<?php

namespace App\Enums;

enum SlaAppliesTo: string
{
    case Tickets = 'tickets'; // tickets sla applies to
    case Complaints = 'complaints'; // complaints sla applies to
    case Inquiries = 'inquiries'; // inquiries sla applies to
}
