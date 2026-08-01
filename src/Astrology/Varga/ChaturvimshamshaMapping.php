<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Varga;

use Vittix\Panchang\Enum\Rashi;

/**
 * Maps a longitude to the Chaturvimshamsha chart (D24).
 */
final class ChaturvimshamshaMapping implements VargaMappingInterface
{
    public function getRashi(float $longitude): Rashi
    {
        $rashiIndex = (int) floor($longitude / 30.0);
        $degreeInSign = fmod($longitude, 30.0);
        
        $partIndex = (int) floor($degreeInSign / 1.25);
        
        $isOddSign = ($rashiIndex % 2 === 0);

        $startSign = $isOddSign ? 4 : 3;
        
        $mappedIndex = ($startSign + $partIndex) % 12;

        return Rashi::fromIndex($mappedIndex);
    }
}
