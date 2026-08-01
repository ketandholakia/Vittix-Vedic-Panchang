<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Varga;

use Vittix\Panchang\Enum\Rashi;

/**
 * Maps a longitude to the Hora chart (D2).
 */
final class HoraMapping implements VargaMappingInterface
{
    public function getRashi(float $longitude): Rashi
    {
        $rashiIndex = (int) floor($longitude / 30.0);
        $degreeInSign = fmod($longitude, 30.0);
        
        $isOddSign = ($rashiIndex % 2 === 0); // 0 (Aries), 2 (Gemini), etc.
        $isFirstHalf = ($degreeInSign < 15.0);

        if ($isOddSign) {
            return $isFirstHalf ? Rashi::Simha : Rashi::Karka;
        }

        // Even sign
        return $isFirstHalf ? Rashi::Karka : Rashi::Simha;
    }
}
