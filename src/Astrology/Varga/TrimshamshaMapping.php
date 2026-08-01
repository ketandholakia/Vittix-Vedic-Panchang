<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Varga;

use Vittix\Panchang\Enum\Rashi;

/**
 * Maps a longitude to the Trimshamsha chart (D30).
 * Note: D30 uses irregular degree spans based on planetary rulerships.
 */
final class TrimshamshaMapping implements VargaMappingInterface
{
    public function getRashi(float $longitude): Rashi
    {
        $rashiIndex = (int) floor($longitude / 30.0);
        $degreeInSign = fmod($longitude, 30.0);
        
        $isOddSign = ($rashiIndex % 2 === 0);

        if ($isOddSign) {
            if ($degreeInSign < 5.0) {
                $mappedIndex = 0; // Aries (Mars)
            } elseif ($degreeInSign < 10.0) {
                $mappedIndex = 10; // Aquarius (Saturn)
            } elseif ($degreeInSign < 18.0) {
                $mappedIndex = 8; // Sagittarius (Jupiter)
            } elseif ($degreeInSign < 25.0) {
                $mappedIndex = 2; // Gemini (Mercury)
            } else {
                $mappedIndex = 6; // Libra (Venus)
            }
        } else {
            if ($degreeInSign < 5.0) {
                $mappedIndex = 1; // Taurus (Venus)
            } elseif ($degreeInSign < 12.0) {
                $mappedIndex = 5; // Virgo (Mercury)
            } elseif ($degreeInSign < 20.0) {
                $mappedIndex = 11; // Pisces (Jupiter)
            } elseif ($degreeInSign < 25.0) {
                $mappedIndex = 9; // Capricorn (Saturn)
            } else {
                $mappedIndex = 7; // Scorpio (Mars)
            }
        }

        return Rashi::fromIndex($mappedIndex);
    }
}
