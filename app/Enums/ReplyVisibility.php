<?php

namespace App\Enums;

enum ReplyVisibility: string
{
    case Public = 'public'; // public reply visibility
    case Internal = 'internal'; // internal reply visibility
}
