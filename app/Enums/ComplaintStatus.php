<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case New = 'new'; // new complaint
    case UnderReview = 'under_review'; // complaint under review
    case WaitingCustomer = 'waiting_customer';
    case Escalated = 'escalated';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
