<?php

namespace App\Enums;

enum CompanyStatus: string
{
    case Active = 'active'; // active company
    case Suspended = 'suspended'; // suspended company
    case Trialing = 'trialing'; // trialing company
    case Archived = 'archived'; // archived company
}
