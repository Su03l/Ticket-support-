<?php

namespace App\Enums;

enum ArticleVisibility: string
{
    case Public = 'public'; // public to all users
    case CustomersOnly = 'customers_only'; // public to customers only
    case Internal = 'internal'; // internal to agents only
}
