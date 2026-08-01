<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Varga;

use Vittix\Panchang\Enum\Rashi;

/**
 * Maps a longitude to the Saptamsha chart (D7).
 */
final class SaptamshaMapping implements VargaMappingInterface
{
    public function getRashi(float $longitude): Rashi
    {
        $rashiIndex = (int) floor($longitude / 30.0);
        $degreeInSign = fmod($longitude, 30.0);
        
        $partLength = 30.0 / 7.0;
        $partIndex = (int) floor($degreeInSign / $partLength);
        
        $isOddSign = ($rashiIndex % 2 === 0); // 0 (Aries) is technically the 1st sign (Odd)

        if ($isOddSign) {
            $mappedIndex = ($rashiIndex + $partIndex) % 12;
        } else {
            // Even sign starts from the 7th sign from itself (+6 index)
            $mappedIndex = ($rashiIndex + 6 + $partIndex) % 12;
        }

        return Rashi::fromIndex($mappedIndex);
    }
}
