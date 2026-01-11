<?php

namespace App\Enum;

enum CompetitionType: string
{
    CASE COMPANY = 'company';
    CASE FIRE = 'fire';
    CASE CUP = 'cup';
    CASE ROUNDS = 'rounds';

    public function getLabel(): string
    {
        return match ($this) {
            self::COMPANY => 'Betriebeschießen',
            self::FIRE => 'Feuerwehrschießen',
            self::CUP => 'Pokalschießen',
            self::ROUNDS => 'Rundenwettkampf',
        };
    }
}
