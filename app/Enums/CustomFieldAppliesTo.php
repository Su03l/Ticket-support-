<?php

namespace App\Enums;

enum CustomFieldAppliesTo: string
{
    case Ticket = 'ticket'; // custom fields for ticket
    case Complaint = 'complaint'; // custom fields for complaint
    case Inquiry = 'inquiry'; // custom fields for inquiry
    case Customer = 'customer'; // custom fields for customer
}
