<?php

namespace App\Enums;

enum ArticleVisibility: string
{
    case Public = 'public';
    case CustomersOnly = 'customers_only';
    case Internal = 'internal';
}
