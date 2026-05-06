<?php

namespace App\Enums;

enum BillingCycle: string
{
    case Monthly = 'monthly'; // monthly billing cycle
    case Yearly = 'yearly'; // yearly billing cycle
}
