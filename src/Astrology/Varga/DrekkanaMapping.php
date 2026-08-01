<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Varga;

use Vittix\Panchang\Enum\Rashi;

/**
 * Maps a longitude to the Drekkana chart (D3).
 */
final class DrekkanaMapping implements VargaMappingInterface
{
    public function getRashi(float $longitude): Rashi
    {
        $rashiIndex = (int) floor($longitude / 30.0);
        $degreeInSign = fmod($longitude, 30.0);
        
        $decanateIndex = (int) floor($degreeInSign / 10.0);

        // 1st decanate (0) = same sign
        // 2nd decanate (1) = 5th sign
        // 3rd decanate (2) = 9th sign
        $mappedIndex = ($rashiIndex + ($decanateIndex * 4)) % 12;

        return Rashi::fromIndex($mappedIndex);
    }
}
