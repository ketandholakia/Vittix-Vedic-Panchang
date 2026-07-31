<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * The 27 Nakshatras (lunar mansions), each spanning 13°20' of sidereal longitude.
 *
 * Index 0 → Ashwini (0°–13°20′ sidereal)
 * Index 26 → Revati (346°40′–360°)
 */
enum Nakshatra: int
{
    case Ashwini     = 0;
    case Bharani     = 1;
    case Krittika    = 2;
    case Rohini      = 3;
    case Mrigashira  = 4;
    case Ardra       = 5;
    case Punarvasu   = 6;
    case Pushya      = 7;
    case Ashlesha    = 8;
    case Magha       = 9;
    case PurvaPhalguni = 10;
    case UttaraPhalguni = 11;
    case Hasta       = 12;
    case Chitra      = 13;
    case Swati       = 14;
    case Vishakha    = 15;
    case Anuradha    = 16;
    case Jyeshtha    = 17;
    case Mula        = 18;
    case PurvaAshadha = 19;
    case UttaraAshadha = 20;
    case Shravana    = 21;
    case Dhanishtha  = 22;
    case Shatabhisha = 23;
    case PurvaBhadrapada = 24;
    case UttaraBhadrapada = 25;
    case Revati      = 26;

    public function nameEnglish(): string
    {
        return match ($this) {
            self::Ashwini         => 'Ashwini',
            self::Bharani         => 'Bharani',
            self::Krittika        => 'Krittika',
            self::Rohini          => 'Rohini',
            self::Mrigashira      => 'Mrigashira',
            self::Ardra           => 'Ardra',
            self::Punarvasu       => 'Punarvasu',
            self::Pushya          => 'Pushya',
            self::Ashlesha        => 'Ashlesha',
            self::Magha           => 'Magha',
            self::PurvaPhalguni   => 'Purva Phalguni',
            self::UttaraPhalguni  => 'Uttara Phalguni',
            self::Hasta           => 'Hasta',
            self::Chitra          => 'Chitra',
            self::Swati           => 'Swati',
            self::Vishakha        => 'Vishakha',
            self::Anuradha        => 'Anuradha',
            self::Jyeshtha        => 'Jyeshtha',
            self::Mula            => 'Mula',
            self::PurvaAshadha    => 'Purva Ashadha',
            self::UttaraAshadha   => 'Uttara Ashadha',
            self::Shravana        => 'Shravana',
            self::Dhanishtha      => 'Dhanishtha',
            self::Shatabhisha     => 'Shatabhisha',
            self::PurvaBhadrapada => 'Purva Bhadrapada',
            self::UttaraBhadrapada => 'Uttara Bhadrapada',
            self::Revati          => 'Revati',
        };
    }



    /**
     * Derive from 0-based sidereal longitude index.
     */
    public static function fromIndex(int $index): self
    {
        return self::from($index % 27);
    }
}
