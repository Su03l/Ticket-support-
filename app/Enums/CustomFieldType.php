<?php

namespace App\Enums;

enum CustomFieldType: string
{
    case Text = 'text'; // text custom field
    case Textarea = 'textarea'; // textarea custom field
    case Email = 'email'; // email custom field
    case Phone = 'phone'; // phone custom field
    case Number = 'number';
    case Date = 'date';
    case Select = 'select';
    case MultiSelect = 'multi_select';
    case Checkbox = 'checkbox';
    case File = 'file';
}
