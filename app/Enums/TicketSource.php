<?php

namespace App\Enums;

enum TicketSource: string
{
    case Web = 'web';
    case Email = 'email';
    case Whatsapp = 'whatsapp';
    case Api = 'api';
    case Internal = 'internal';
}
