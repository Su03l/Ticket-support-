<?php

namespace App\Enums;

enum CompanyThemeMode: string
{
    case Light = 'light'; // light theme mode
    case Dark = 'dark'; // dark theme mode
    case System = 'system'; // system theme mode
}
