<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Shadbala;

use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\ValueObject\PlanetPlacement;

/**
 * Calculates Chesta Bala (Motional Strength).
 * 
 * In Vedic astrology, a planet gains strength when it is retrograde (Vakra)
 * or stationary (Vikala). Direct and fast-moving planets have less motional strength.
 */
final class ChestaBalaCalculator
{
    public function calculate(PlanetPlacement $placement): float
    {
        $body = $placement->body;
        $motion = $placement->dailyMotion;

        if ($motion === null) {
            return 0.0;
        }

        // Sun and Moon do not have retrograde motion. 
        // Their Chesta Bala is equivalent to their Ayana and Paksha Bala respectively, 
        // which are calculated elsewhere. We return 0 here to avoid double-counting.
        if (in_array($body, [Body::Sun, Body::Moon], true)) {
            return 0.0;
        }

        // Tara Grahas (Mars, Mercury, Jupiter, Venus, Saturn)
        if ($motion < -0.01) {
            // Retrograde (Vakra) gets full 60 virupas
            return 60.0;
        }

        if (abs($motion) <= 0.01) {
            // Stationary (Vikala) gets half strength
            return 30.0;
        }

        // Direct motion (Riju)
        // A full traditional calculation uses the Seeghrocha and Chesta Kendra.
        // A simplified proxy is to assign 15-30 virupas for normal direct motion,
        // inversely proportional to speed (faster = weaker).
        // For this engine, we assign a baseline of 15 virupas for forward motion.
        return 15.0;
    }
}
