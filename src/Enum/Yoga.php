<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * The 27 Yogas.
 *
 * Each Yoga spans 13°20′ of the combined sidereal Sun + Moon longitude.
 * yogaLongitude = normalize(siderealSun + siderealMoon)
 * index = floor(yogaLongitude / (360/27))
 */
enum Yoga: int
{
    case Vishkumbha  = 0;
    case Preeti      = 1;
    case Ayushman    = 2;
    case Saubhagya   = 3;
    case Shobhana    = 4;
    case Atiganda    = 5;
    case Sukarman    = 6;
    case Dhriti      = 7;
    case Shoola      = 8;
    case Ganda       = 9;
    case Vriddhi     = 10;
    case Dhruva      = 11;
    case Vyaghata    = 12;
    case Harshana    = 13;
    case Vajra       = 14;
    case Siddhi      = 15;
    case Vyatipata   = 16;
    case Variyana    = 17;
    case Parigha     = 18;
    case Shiva       = 19;
    case Siddha      = 20;
    case Sadhya      = 21;
    case Shubha      = 22;
    case Shukla      = 23;
    case Brahma      = 24;
    case Indra       = 25;
    case Vaidhriti   = 26;

    public function nameEnglish(): string
    {
        return match ($this) {
            self::Vishkumbha => 'Vishkumbha',
            self::Preeti     => 'Preeti',
            self::Ayushman   => 'Ayushman',
            self::Saubhagya  => 'Saubhagya',
            self::Shobhana   => 'Shobhana',
            self::Atiganda   => 'Atiganda',
            self::Sukarman   => 'Sukarman',
            self::Dhriti     => 'Dhriti',
            self::Shoola     => 'Shoola',
            self::Ganda      => 'Ganda',
            self::Vriddhi    => 'Vriddhi',
            self::Dhruva     => 'Dhruva',
            self::Vyaghata   => 'Vyaghata',
            self::Harshana   => 'Harshana',
            self::Vajra      => 'Vajra',
            self::Siddhi     => 'Siddhi',
            self::Vyatipata  => 'Vyatipata',
            self::Variyana   => 'Variyana',
            self::Parigha    => 'Parigha',
            self::Shiva      => 'Shiva',
            self::Siddha     => 'Siddha',
            self::Sadhya     => 'Sadhya',
            self::Shubha     => 'Shubha',
            self::Shukla     => 'Shukla',
            self::Brahma     => 'Brahma',
            self::Indra      => 'Indra',
            self::Vaidhriti  => 'Vaidhriti',
        };
    }

    public static function fromIndex(int $index): self
    {
        return self::from($index % 27);
    }
}
