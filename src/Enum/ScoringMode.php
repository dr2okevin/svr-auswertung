<?php

namespace App\Enum;

enum ScoringMode: string
{
    case FULL_RINGS = 'full_rings';
    case TENTH = 'tenth';

    public function getLabel(): string
    {
        return match ($this) {
            self::FULL_RINGS => 'Volle Ringe',
            self::TENTH => 'Zehntel',
        };
    }
}
