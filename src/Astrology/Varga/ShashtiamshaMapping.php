<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Varga;

use Vittix\Panchang\Enum\Rashi;

/**
 * Maps a longitude to the Shashtiamsha chart (D60).
 */
final class ShashtiamshaMapping implements VargaMappingInterface
{
    public function getRashi(float $longitude): Rashi
    {
        $rashiIndex = (int) floor($longitude / 30.0);
        $degreeInSign = fmod($longitude, 30.0);
        
        $partIndex = (int) floor($degreeInSign / 0.5);
        
        $mappedIndex = ($rashiIndex + $partIndex) % 12;

        return Rashi::fromIndex($mappedIndex);
    }
}
