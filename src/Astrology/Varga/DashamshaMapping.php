<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Varga;

use Vittix\Panchang\Enum\Rashi;

/**
 * Maps a longitude to the Dashamsha chart (D10).
 */
final class DashamshaMapping implements VargaMappingInterface
{
    public function getRashi(float $longitude): Rashi
    {
        $rashiIndex = (int) floor($longitude / 30.0);
        $degreeInSign = fmod($longitude, 30.0);
        
        $partIndex = (int) floor($degreeInSign / 3.0); // 0 to 9
        
        $isOddSign = ($rashiIndex % 2 === 0);

        if ($isOddSign) {
            $mappedIndex = ($rashiIndex + $partIndex) % 12;
        } else {
            // Even sign starts from the 9th sign (which is index + 8)
            $mappedIndex = ($rashiIndex + 8 + $partIndex) % 12;
        }

        return Rashi::fromIndex($mappedIndex);
    }
}
