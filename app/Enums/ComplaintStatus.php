<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case New = 'new'; // new complaint
    case UnderReview = 'under_review'; // complaint under review
    case WaitingCustomer = 'waiting_customer'; // waiting for customer response
    case Escalated = 'escalated'; // escalated complaint
    case Resolved = 'resolved'; // resolved complaint 
    case Closed = 'closed'; // close complaint 
    case Rejected = 'rejected'; // 
    case Cancelled = 'cancelled';
}
