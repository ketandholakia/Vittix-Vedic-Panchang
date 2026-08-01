<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Varga;

use Vittix\Panchang\Enum\Rashi;

/**
 * Maps a longitude to the Saptavimshamsha chart (D27).
 */
final class SaptavimshamshaMapping implements VargaMappingInterface
{
    public function getRashi(float $longitude): Rashi
    {
        $rashiIndex = (int) floor($longitude / 30.0);
        $degreeInSign = fmod($longitude, 30.0);
        
        $partLength = 30.0 / 27.0;
        $partIndex = (int) floor($degreeInSign / $partLength);
        
        $element = $rashiIndex % 4;

        $startSign = match ($element) {
            0 => 0, // Fiery signs start from Aries (0)
            1 => 3, // Earthy signs start from Cancer (3)
            2 => 6, // Airy signs start from Libra (6)
            3 => 9, // Watery signs start from Capricorn (9)
        };
        
        $mappedIndex = ($startSign + $partIndex) % 12;

        return Rashi::fromIndex($mappedIndex);
    }
}


