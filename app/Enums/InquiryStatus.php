<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'new';
    case Open = 'open';
    case Answered = 'answered';
    case WaitingCustomer = 'waiting_customer';
    case ConvertedToTicket = 'converted_to_ticket';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
}
