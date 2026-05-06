<?php

namespace App\Enums;

enum CustomFieldAppliesTo: string
{
    case Ticket = 'ticket';
    case Complaint = 'complaint';
    case Inquiry = 'inquiry';
    case Customer = 'customer';
}
