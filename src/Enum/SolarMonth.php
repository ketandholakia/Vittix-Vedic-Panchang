<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * The 12 solar months (Saura Masa) in the Hindu calendar.
 *
 * Each solar month begins when the Sun enters the corresponding
 * sidereal zodiac sign (Rashi). The names correspond directly to the
 * Rashis but follow the Hindu naming convention.
 *
 * Index 1–12 follows the traditional order starting from Mesha (Aries).
 */
enum SolarMonth: int
{
    case Mesha      = 1;   // Aries     — mid-April to mid-May
    case Vrishabha  = 2;   // Taurus    — mid-May to mid-June
    case Mithuna    = 3;   // Gemini    — mid-June to mid-July
    case Karka      = 4;   // Cancer    — mid-July to mid-August
    case Simha      = 5;   // Leo       — mid-August to mid-September
    case Kanya      = 6;   // Virgo     — mid-September to mid-October
    case Tula       = 7;   // Libra     — mid-October to mid-November
    case Vrischika  = 8;   // Scorpio   — mid-November to mid-December
    case Dhanu      = 9;   // Sagittarius — mid-December to mid-January
    case Makara     = 10;  // Capricorn — mid-January to mid-February
    case Kumbha     = 11;  // Aquarius  — mid-February to mid-March
    case Meena      = 12;  // Pisces    — mid-March to mid-April

    /**
     * English transliteration of the solar month name.
     */
    public function nameEnglish(): string
    {
        return match ($this) {
            self::Mesha     => 'Mesha',
            self::Vrishabha => 'Vrishabha',
            self::Mithuna   => 'Mithuna',
            self::Karka     => 'Karka',
            self::Simha     => 'Simha',
            self::Kanya     => 'Kanya',
            self::Tula      => 'Tula',
            self::Vrischika => 'Vrischika',
            self::Dhanu     => 'Dhanu',
            self::Makara    => 'Makara',
            self::Kumbha    => 'Kumbha',
            self::Meena     => 'Meena',
        };
    }

    /**
     * Western zodiac equivalent name.
     */
    public function westernName(): string
    {
        return match ($this) {
            self::Mesha     => 'Aries',
            self::Vrishabha => 'Taurus',
            self::Mithuna   => 'Gemini',
            self::Karka     => 'Cancer',
            self::Simha     => 'Leo',
            self::Kanya     => 'Virgo',
            self::Tula      => 'Libra',
            self::Vrischika => 'Scorpio',
            self::Dhanu     => 'Sagittarius',
            self::Makara    => 'Capricorn',
            self::Kumbha    => 'Aquarius',
            self::Meena     => 'Pisces',
        };
    }

    /**
     * Derive the solar month from the Sun's sidereal longitude.
     *
     * Each Rashi spans exactly 30° of the sidereal ecliptic.
     * Mesha (1) = 0°–30°, Vrishabha (2) = 30°–60°, etc.
     *
     * @param float $siderealSunLongitude  Sun's sidereal longitude in degrees [0, 360).
     */
    public static function fromSiderealLongitude(float $siderealSunLongitude): self
    {
        $index = ((int) floor($siderealSunLongitude / 30.0)) + 1;

        // Guard against edge case where longitude is exactly 360.0
        if ($index > 12) {
            $index = 1;
        }

        return self::from($index);
    }
}
