<?php

namespace App\Enums;

enum DepartmentStatus: string
{
    case Active = 'active'; // active department status
    case Inactive = 'inactive'; // inactive department status
    case Archived = 'archived';
}
