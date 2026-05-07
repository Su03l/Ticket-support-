<?php

namespace App\Enums;

enum InquiryStatus: string
{
    case New = 'new'; // new inquiry status
    case Open = 'open'; // open inquiry status
    case Answered = 'answered'; // answered inquiry status
    case WaitingCustomer = 'waiting_customer'; // waiting customer inquiry status
    case ConvertedToTicket = 'converted_to_ticket'; // converted to ticket inquiry status
    case Closed = 'closed'; // closed inquiry status
    case Cancelled = 'cancelled';
}
