<?php

namespace App\Enums;

enum NpsCategory: string
{
    case Promoter = 'promoter';
    case Passive = 'passive';
    case Detractor = 'detractor';

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 9 => self::Promoter,
            $score >= 7 => self::Passive,
            default => self::Detractor,
        };
    }
}
