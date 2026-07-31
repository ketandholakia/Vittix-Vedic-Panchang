<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Lunar;

use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Math\Polynomial;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Fundamental arguments for the lunar position series.
 *
 * Notation follows Meeus, "Astronomical Algorithms" Ch. 47:
 *   L′  — Moon's mean longitude
 *   D   — Mean elongation of the Moon
 *   M   — Sun's mean anomaly
 *   M′  — Moon's mean anomaly
 *   F   — Moon's argument of latitude (distance from ascending node)
 *   Ω   — Longitude of Moon's ascending node
 *   E   — Eccentricity of Earth's orbit (applied to terms involving M)
 *
 * All angles returned in degrees [0, 360).
 */
final class LunarArgumentCalculator
{
    /**
     * Calculate all fundamental arguments for a given instant.
     *
     * @return array{
     *     lPrime: float,
     *     d: float,
     *     m: float,
     *     mPrime: float,
     *     f: float,
     *     omega: float,
     *     eccentricity: float,
     *     a1: float,
     *     a2: float,
     *     a3: float,
     * }
     */
    public function calculate(AstroInstant $instant): array
    {
        $t = $instant->julianCenturiesTt();

        // Moon's mean longitude
        $lPrime = Angle::normalize(
            Polynomial::evaluate($t, 218.3164477, 481267.88123421, -0.0015786, 1.0 / 538841.0, -1.0 / 65194000.0)
        );

        // Mean elongation of the Moon
        $d = Angle::normalize(
            Polynomial::evaluate($t, 297.8501921, 445267.1114034, -0.0018819, 1.0 / 545868.0, -1.0 / 113065000.0)
        );

        // Sun's mean anomaly
        $m = Angle::normalize(
            Polynomial::evaluate($t, 357.5291092, 35999.0502909, -0.0001536, 1.0 / 24490000.0)
        );

        // Moon's mean anomaly
        $mPrime = Angle::normalize(
            Polynomial::evaluate($t, 134.9633964, 477198.8675055, 0.0087414, 1.0 / 69699.0, -1.0 / 14712000.0)
        );

        // Moon's argument of latitude
        $f = Angle::normalize(
            Polynomial::evaluate($t, 93.2720950, 483202.0175233, -0.0036539, -1.0 / 3526000.0, 1.0 / 863310000.0)
        );

        // Longitude of ascending node
        $omega = Angle::normalize(
            Polynomial::evaluate($t, 125.0445479, -1934.1362608, 0.0020754, 1.0 / 467441.0, -1.0 / 60616000.0)
        );

        // Eccentricity correction (for terms involving M)
        $eccentricity = Polynomial::evaluate($t, 1.0, -0.002516, -0.0000074);

        // Additional corrections (Meeus §47 p. 338)
        $a1 = Angle::normalize(119.75 + 131.849 * $t);           // Venus perturbation
        $a2 = Angle::normalize(53.09 + 479264.290 * $t);         // Jupiter perturbation
        $a3 = Angle::normalize(313.45 + 481266.484 * $t);        // Flattening

        return compact('lPrime', 'd', 'm', 'mPrime', 'f', 'omega', 'eccentricity', 'a1', 'a2', 'a3');
    }
}
