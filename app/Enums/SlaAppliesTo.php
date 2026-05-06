<?php

namespace App\Enums;

enum SlaAppliesTo: string
{
    case Tickets = 'tickets';
    case Complaints = 'complaints';
    case Inquiries = 'inquiries';
}
