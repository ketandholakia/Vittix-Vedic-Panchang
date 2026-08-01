<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Shadbala;

use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\ValueObject\PlanetPlacement;

/**
 * Calculates Ayana Bala (Equinoctial Strength).
 * 
 * Based on the planet's declination (Kranti). 
 * Maximum declination is approx 24 degrees.
 */
final class AyanaBalaCalculator
{
    public function calculate(PlanetPlacement $placement): float
    {
        $body = $placement->body;
        $declination = $placement->declination;

        if ($declination === null) {
            return 0.0;
        }

        // Clamp declination between -24 and 24 to prevent out-of-bounds Virupas
        $declination = max(-24.0, min(24.0, $declination));

        return match ($body) {
            // Strong in Northern Declination (positive)
            Body::Sun, Body::Mars, Body::Jupiter, Body::Venus => 
                (24.0 + $declination) / 48.0 * 60.0,
                
            // Strong in Southern Declination (negative)
            Body::Moon, Body::Saturn => 
                (24.0 - $declination) / 48.0 * 60.0,
                
            // Mercury is considered strong everywhere in Ayana Bala, 
            // usually given a flat 30 or scaled by absolute declination.
            Body::Mercury => 
                (24.0 + abs($declination)) / 48.0 * 60.0,
                
            default => 0.0,
        };
    }
}
