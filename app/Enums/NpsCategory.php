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
            $score >= 9 => self::Promoter,
            $score >= 7 => self::Passive,
            default => self::Detractor,
        };
    }
}
