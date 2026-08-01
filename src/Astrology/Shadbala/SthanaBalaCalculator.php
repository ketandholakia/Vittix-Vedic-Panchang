<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Shadbala;

use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\ValueObject\PlanetPlacement;

/**
 * Calculates Sthana Bala (Positional Strength).
 * Currently implements Uchcha Bala (Exaltation strength) and Kendradi Bala.
 */
final class SthanaBalaCalculator
{
    private const EXALTATION_DEGREES = [
        Body::Sun->value => 10.0,
        Body::Moon->value => 33.0,   // 3 Taurus
        Body::Mars->value => 298.0,  // 28 Capricorn
        Body::Mercury->value => 165.0, // 15 Virgo
        Body::Jupiter->value => 95.0,  // 5 Cancer
        Body::Venus->value => 357.0,   // 27 Pisces
        Body::Saturn->value => 200.0,  // 20 Libra
    ];

    public function calculate(PlanetPlacement $placement): float
    {
        return $this->uchchaBala($placement) + $this->kendradiBala($placement);
    }

    private function uchchaBala(PlanetPlacement $placement): float
    {
        $exaltation = self::EXALTATION_DEGREES[$placement->body->value] ?? null;

        if ($exaltation === null) {
            return 0.0;
        }

        $debilitation = Angle::normalize($exaltation + 180.0);
        
        $distance = abs($placement->longitude - $debilitation);
        
        if ($distance > 180.0) {
            $distance = 360.0 - $distance;
        }

        return $distance / 3.0; // Max 60
    }

    private function kendradiBala(PlanetPlacement $placement): float
    {
        return match ($placement->house) {
            1, 4, 7, 10 => 60.0, // Kendra
            2, 5, 8, 11 => 30.0, // Panaphara
            3, 6, 9, 12 => 15.0, // Apoklima
            default => 0.0,
        };
    }
}
