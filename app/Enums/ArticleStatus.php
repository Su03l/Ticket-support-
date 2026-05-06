<?php

namespace App\Enums;

enum ArticleStatus: string
{
    case Draft = 'draft'; // not published yet
    case Published = 'published'; // published 
    case Archived = 'archived'; // archived 
}
