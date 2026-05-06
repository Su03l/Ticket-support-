<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case New = 'new';
    case UnderReview = 'under_review';
    case WaitingCustomer = 'waiting_customer';
    case Escalated = 'escalated';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
