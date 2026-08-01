<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Shadbala;

use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\ValueObject\PlanetPlacement;

/**
 * Calculates Dik Bala (Directional Strength).
 */
final class DikBalaCalculator
{
    public function calculate(PlanetPlacement $placement): float
    {
        $weakestHouse = match ($placement->body) {
            Body::Sun, Body::Mars => 4,     // Max strength in 10th
            Body::Moon, Body::Venus => 10,  // Max strength in 4th
            Body::Jupiter, Body::Mercury => 7, // Max strength in 1st
            Body::Saturn => 1,              // Max strength in 7th
            default => null,
        };

        if ($weakestHouse === null) {
            return 0.0;
        }

        // Calculate distance from weakest house counting forward
        $distance = $placement->house - $weakestHouse;
        
        if ($distance < 0) {
            $distance += 12;
        }

        // If distance is greater than 6 signs (180 degrees), we take the inverse distance
        if ($distance > 6) {
            $distance = 12 - $distance;
        }

        // Each sign/house of distance gives 10 virupas (max 60 at 6 houses away)
        return $distance * 10.0;
    }
}
