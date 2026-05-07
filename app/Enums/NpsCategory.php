<?php

namespace App\Enums;

enum NpsCategory: string
{
    case Promoter = 'promoter'; // promoter category
    case Passive = 'passive'; // passive category
    case Detractor = 'detractor'; // detractor category

    // get nps category from score
    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 9 => self::Promoter, // promoter category when score is 9 or 10
            $score >= 7 => self::Passive, // passive category when score is 7 or 8
            default => self::Detractor, // detractor category when score is 0 to 6
        };
    }
}
