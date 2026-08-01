<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Varga;

use Vittix\Panchang\Enum\Rashi;

/**
 * Maps a longitude to the Khavedamsha / Swavedamsha chart (D40).
 */
final class KhavedamshaMapping implements VargaMappingInterface
{
    public function getRashi(float $longitude): Rashi
    {
        $rashiIndex = (int) floor($longitude / 30.0);
        $degreeInSign = fmod($longitude, 30.0);
        
        $partIndex = (int) floor($degreeInSign / 0.75);
        
        $isOddSign = ($rashiIndex % 2 === 0);

        $startSign = $isOddSign ? 0 : 6;
        
        $mappedIndex = ($startSign + $partIndex) % 12;

        return Rashi::fromIndex($mappedIndex);
    }
}
