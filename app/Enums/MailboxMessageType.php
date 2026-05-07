<?php

namespace App\Enums;

enum MailboxMessageType: string
{
    case System = 'system'; // system message type
    case Ticket = 'ticket'; // ticket message type
    case Complaint = 'complaint'; // complaint message type
    case Inquiry = 'inquiry'; // inquiry message type
    case Notification = 'notification'; // notification message type
    case AdminNotice = 'admin_notice';
    case Assignment = 'assignment';
    case Escalation = 'escalation';
}
