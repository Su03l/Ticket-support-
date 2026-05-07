<?php

namespace App\Enums;

enum DepartmentStatus: string
{
    case Active = 'active'; // active department status
    case Inactive = 'inactive';
    case Archived = 'archived';
}
