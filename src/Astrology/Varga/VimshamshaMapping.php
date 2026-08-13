<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Varga;

use Vittix\Panchang\Enum\Rashi;

/**
 * Maps a longitude to the Vimshamsha chart (D20).
 */
final class VimshamshaMapping implements VargaMappingInterface
{
    public function getRashi(float $longitude): Rashi
    {
        $rashiIndex = (int) floor($longitude / 30.0);
        $degreeInSign = fmod($longitude, 30.0);
        
        $partIndex = (int) floor($degreeInSign / 1.5);
        
        $modality = $rashiIndex % 3;

        $startSign = match ($modality) {
            0 => 0, // Movable signs start from Aries (0)
            1 => 8, // Fixed signs start from Sagittarius (8)
            2 => 4, // Dual signs start from Leo (4)
            default => throw new \LogicException('Unreachable element type')
        };
        
        $mappedIndex = ($startSign + $partIndex) % 12;

        return Rashi::fromIndex($mappedIndex);
    }
}


