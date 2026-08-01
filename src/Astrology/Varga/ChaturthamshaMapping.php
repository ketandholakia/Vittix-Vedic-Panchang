<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Varga;

use Vittix\Panchang\Enum\Rashi;

/**
 * Maps a longitude to the Chaturthamsha chart (D4).
 */
final class ChaturthamshaMapping implements VargaMappingInterface
{
    public function getRashi(float $longitude): Rashi
    {
        $rashiIndex = (int) floor($longitude / 30.0);
        $degreeInSign = fmod($longitude, 30.0);
        
        // 4 parts of 7.5 degrees each
        $partIndex = (int) floor($degreeInSign / 7.5);
        
        $mappedIndex = ($rashiIndex + ($partIndex * 3)) % 12;

        return Rashi::fromIndex($mappedIndex);
    }
}
