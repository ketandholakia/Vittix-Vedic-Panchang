<?php

declare(strict_types=1);

namespace Vittix\Panchang\Electional;

use Vittix\Panchang\Enum\Karana;
use Vittix\Panchang\Enum\Nakshatra;
use Vittix\Panchang\Enum\Tithi;

/**
 * Evaluates the auspiciousness of a given day by checking various Yogas and Doshas.
 */
final class ElectionalEvaluator
{
    /**
     * Checks if the given Weekday + Nakshatra combination forms Amrit Siddhi Yoga.
     * Amrit Siddhi Yoga is highly auspicious.
     *
     * @param int $weekdayIndex 0 = Sunday, 1 = Monday, etc.
     */
    public function hasAmritSiddhiYoga(int $weekdayIndex, Nakshatra $nakshatra): bool
    {
        return match ($weekdayIndex) {
            0 => $nakshatra === Nakshatra::Hasta,        // Sunday + Hasta
            1 => $nakshatra === Nakshatra::Mrigashira, // Monday + Mrigashira
            2 => $nakshatra === Nakshatra::Ashwini,      // Tuesday + Ashwini
            3 => $nakshatra === Nakshatra::Anuradha,     // Wednesday + Anuradha
            4 => $nakshatra === Nakshatra::Pushya,       // Thursday + Pushya
            5 => $nakshatra === Nakshatra::Revati,       // Friday + Revati
            6 => $nakshatra === Nakshatra::Rohini,       // Saturday + Rohini
            default => false,
        };
    }

    /**
     * Checks if the given Weekday + Nakshatra combination forms Sarvartha Siddhi Yoga.
     */
    public function hasSarvarthaSiddhiYoga(int $weekdayIndex, Nakshatra $nakshatra): bool
    {
        $sarvarthaSiddhi = [
            0 => [Nakshatra::Hasta, Nakshatra::Mula, Nakshatra::UttaraPhalguni, Nakshatra::UttaraAshadha, Nakshatra::UttaraBhadrapada, Nakshatra::Pushya, Nakshatra::Ashwini], // Sunday
            1 => [Nakshatra::Shravana, Nakshatra::Rohini, Nakshatra::Mrigashira, Nakshatra::Pushya, Nakshatra::Anuradha], // Monday
            2 => [Nakshatra::Ashwini, Nakshatra::UttaraBhadrapada, Nakshatra::Krittika, Nakshatra::Ashlesha], // Tuesday
            3 => [Nakshatra::Rohini, Nakshatra::Anuradha, Nakshatra::Hasta, Nakshatra::Krittika, Nakshatra::Mrigashira], // Wednesday
            4 => [Nakshatra::Revati, Nakshatra::Anuradha, Nakshatra::Ashwini, Nakshatra::Punarvasu, Nakshatra::Pushya], // Thursday
            5 => [Nakshatra::Revati, Nakshatra::Anuradha, Nakshatra::Ashwini, Nakshatra::Punarvasu, Nakshatra::Shravana], // Friday
            6 => [Nakshatra::Shravana, Nakshatra::Rohini, Nakshatra::Swati, Nakshatra::Revati] // Saturday
        ];

        return in_array($nakshatra, $sarvarthaSiddhi[$weekdayIndex] ?? [], true);
    }

    /**
     * Checks if it is Guru Pushya Yoga (Thursday + Pushya Nakshatra).
     * Extremely auspicious for starting new ventures, buying gold, etc.
     */
    public function hasGuruPushyaYoga(int $weekdayIndex, Nakshatra $nakshatra): bool
    {
        return $weekdayIndex === 4 && $nakshatra === Nakshatra::Pushya;
    }

    /**
     * Checks if it is Ravi Pushya Yoga (Sunday + Pushya Nakshatra).
     */
    public function hasRaviPushyaYoga(int $weekdayIndex, Nakshatra $nakshatra): bool
    {
        return $weekdayIndex === 0 && $nakshatra === Nakshatra::Pushya;
    }

    /**
     * Determines the Disha Shool (inauspicious direction of travel) for the given weekday.
     *
     * @return string The direction that should be avoided.
     */
    public function getDishaShool(int $weekdayIndex): string
    {
        return match ($weekdayIndex) {
            1, 6 => 'East',      // Monday, Saturday
            0, 5 => 'West',      // Sunday, Friday
            4    => 'South',     // Thursday
            2, 3 => 'North',     // Tuesday, Wednesday
            default => 'None',
        };
    }

    /**
     * Checks if the Moon is traversing through the Panchak Nakshatras.
     * Panchak starts from the 3rd pada of Dhanishta and ends at Revati.
     * Since we only have the Nakshatra here, we consider the entire Nakshatras:
     * Shatabhisha, Purva Bhadrapada, Uttara Bhadrapada, Revati, and partly Dhanishta.
     */
    public function isPanchakaDosha(Nakshatra $nakshatra): bool
    {
        return in_array($nakshatra, [
            Nakshatra::Dhanishtha, // Technically only the 3rd and 4th pada
            Nakshatra::Shatabhisha,
            Nakshatra::PurvaBhadrapada,
            Nakshatra::UttaraBhadrapada,
            Nakshatra::Revati,
        ], true);
    }

    /**
     * Checks if the Karana is Vishti (also known as Bhadra).
     * Vishti Karana is considered highly inauspicious for most good work.
     */
    public function isBhadraActive(Karana $karana): bool
    {
        return $karana === Karana::Vishti;
    }
}
