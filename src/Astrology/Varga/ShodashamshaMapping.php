<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Varga;

use Vittix\Panchang\Enum\Rashi;

/**
 * Maps a longitude to the Shodashamsha chart (D16).
 */
final class ShodashamshaMapping implements VargaMappingInterface
{
    public function getRashi(float $longitude): Rashi
    {
        $rashiIndex = (int) floor($longitude / 30.0);
        $degreeInSign = fmod($longitude, 30.0);
        
        $partLength = 30.0 / 16.0;
        $partIndex = (int) floor($degreeInSign / $partLength);
        
        $modality = $rashiIndex % 3;

        $startSign = match ($modality) {
            0 => 0, // Movable signs start from Aries (0)
            1 => 4, // Fixed signs start from Leo (4)
            2 => 8, // Dual signs start from Sagittarius (8)
            default => 0,
        };
        
        $mappedIndex = ($startSign + $partIndex) % 12;

        return Rashi::fromIndex($mappedIndex);
    }
}


