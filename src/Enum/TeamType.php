<?php

namespace App\Enum;

enum TeamType: string
{
    case COMPANY = 'company';
    case FIRE_DEPARTMENT = 'fire department';
    case POLICE = 'police';
    case CLUB = 'club';
    case OTHER = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::COMPANY => 'Betrieb',
            self::FIRE_DEPARTMENT => 'Feuerwehr',
            self::POLICE => 'Polizei',
            self::CLUB => 'Verein',
            self::OTHER => 'Sonstige'
        };
    }
}
