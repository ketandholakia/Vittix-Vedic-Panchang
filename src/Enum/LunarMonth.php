<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * The 12 named lunar months in the Hindu calendar.
 *
 * The month name is determined by the Nakshatra in which the full moon occurs
 * (Purnimanta system) or by which solar sign the Sun enters during the lunar
 * month (Amanta system).
 *
 * Index 1–12 follows the traditional order starting from Chaitra.
 */
enum LunarMonth: int
{
    case Chaitra    = 1;
    case Vaishakha  = 2;
    case Jyeshtha   = 3;
    case Ashadha    = 4;
    case Shravana   = 5;
    case Bhadrapada = 6;
    case Ashwina    = 7;
    case Kartika    = 8;
    case Margashirsha = 9;
    case Pausha     = 10;
    case Magha      = 11;
    case Phalguna   = 12;

    public function nameEnglish(): string
    {
        return match ($this) {
            self::Chaitra      => 'Chaitra',
            self::Vaishakha    => 'Vaishakha',
            self::Jyeshtha     => 'Jyeshtha',
            self::Ashadha      => 'Ashadha',
            self::Shravana     => 'Shravana',
            self::Bhadrapada   => 'Bhadrapada',
            self::Ashwina      => 'Ashwina',
            self::Kartika      => 'Kartika',
            self::Margashirsha => 'Margashirsha',
            self::Pausha       => 'Pausha',
            self::Magha        => 'Magha',
            self::Phalguna     => 'Phalguna',
        };
    }

}
