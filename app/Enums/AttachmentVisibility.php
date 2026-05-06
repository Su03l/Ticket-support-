<?php

namespace App\Enums;

enum AttachmentVisibility: string
{
    case Public = 'public'; // public to all users
    case Internal = 'internal'; // internal to agents only
}
