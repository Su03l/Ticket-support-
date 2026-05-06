<?php

namespace App\Enums;

enum MailboxMessageType: string
{
    case System = 'system';
    case Ticket = 'ticket';
    case Complaint = 'complaint';
    case Inquiry = 'inquiry';
    case AdminNotice = 'admin_notice';
    case Assignment = 'assignment';
    case Escalation = 'escalation';
}
