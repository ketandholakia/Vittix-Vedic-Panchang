<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Shadbala;

use Vittix\Panchang\Enum\Body;

/**
 * Calculates Naisargika Bala (Natural Strength).
 * This is a constant value for each planet representing its inherent luminosity/strength.
 */
final class NaisargikaBalaCalculator
{
    private const VALUES = [
        Body::Sun->value => 60.0,
        Body::Moon->value => 51.428,    // 60 * 6/7
        Body::Venus->value => 42.857,   // 60 * 5/7
        Body::Jupiter->value => 34.285, // 60 * 4/7
        Body::Mercury->value => 25.714, // 60 * 3/7
        Body::Mars->value => 17.142,    // 60 * 2/7
        Body::Saturn->value => 8.571,   // 60 * 1/7
    ];

    public function calculate(Body $body): float
    {
        return self::VALUES[$body->value] ?? 0.0;
    }
}
