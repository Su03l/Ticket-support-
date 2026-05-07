<?php

namespace App\Enums;

enum CustomFieldType: string
{
    case Text = 'text'; // text custom field
    case Textarea = 'textarea'; // textarea custom field
    case Email = 'email'; // email custom field
    case Phone = 'phone'; // phone custom field
    case Number = 'number'; // number custom field
    case Date = 'date'; // date custom field
    case Select = 'select'; // select custom field
    case MultiSelect = 'multi_select';
    case Checkbox = 'checkbox';
    case File = 'file';
}
