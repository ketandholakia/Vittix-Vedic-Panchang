<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Coordinates;

use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Math\Polynomial;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * IAU 1980 Nutation in Longitude (Δψ) and Obliquity (Δε).
 *
 * This is a 106-term series (truncated to the most significant 63 terms in
 * Meeus Table 22.A) giving accuracy of approximately 0.5 arcseconds, which
 * is well within the requirements of Panchang calculation.
 *
 * Reference: Jean Meeus, "Astronomical Algorithms", 2nd ed., Chapter 22.
 */
final class NutationCalculator
{
    private const TERMS = [
        //  D   M  M'  F  Om   ΔψS0     ΔψS1   ΔεS0  ΔεC0
        [  0,  0,  0,  0,  1, -171996, -174.2, 92025,  8.9],
        [ -2,  0,  0,  2,  2,  -13187,   -1.6,  5736, -3.1],
        [  0,  0,  0,  2,  2,   -2274,   -0.2,   977, -0.5],
        [  0,  0,  0,  0,  2,    2062,    0.2,  -895,  0.5],
        [  0,  1,  0,  0,  0,    1426,   -3.4,    54, -0.1],
        [  0,  0,  1,  0,  0,     712,    0.1,    -7,  0.0],
        [ -2,  1,  0,  2,  2,    -517,    1.2,   224, -0.6],
        [  0,  0,  0,  2,  1,    -386,   -0.4,   200,  0.0],
        [  0,  0,  1,  2,  2,    -301,    0.0,   129, -0.1],
        [ -2, -1,  0,  2,  2,     217,   -0.5,   -95,  0.3],
        [ -2,  0,  1,  0,  0,    -158,    0.0,     0,  0.0],
        [ -2,  0,  0,  2,  1,     129,    0.1,   -70,  0.0],
        [  0,  0, -1,  2,  2,     123,    0.0,   -53,  0.0],
        [  2,  0,  0,  0,  0,      63,    0.0,     0,  0.0],
        [  0,  0,  1,  0,  1,      63,    0.1,   -33,  0.0],
        [  2,  0, -1,  2,  2,     -59,    0.0,    26,  0.0],
        [  0,  0, -1,  0,  1,     -58,   -0.1,    32,  0.0],
        [  0,  0,  1,  2,  1,     -51,    0.0,    27,  0.0],
        [ -2,  0,  2,  0,  0,      48,    0.0,     0,  0.0],
        [  0,  0, -2,  2,  1,      46,    0.0,   -24,  0.0],
        [  2,  0,  0,  2,  2,     -38,    0.0,    16,  0.0],
        [  0,  0,  2,  2,  2,     -31,    0.0,    13,  0.0],
        [  0,  0,  2,  0,  0,      29,    0.0,     0,  0.0],
        [ -2,  0,  1,  2,  2,      29,    0.0,   -12,  0.0],
        [  0,  0,  0,  2,  0,      26,    0.0,     0,  0.0],
        [ -2,  0,  0,  2,  0,     -22,    0.0,     0,  0.0],
        [  0,  0, -1,  2,  1,      21,    0.0,   -10,  0.0],
        [  0,  2,  0,  0,  0,      17,   -0.1,     0,  0.0],
        [  2,  0, -1,  0,  1,      16,    0.0,    -8,  0.0],
        [ -2,  2,  0,  2,  2,     -16,    0.1,     7,  0.0],
        [  0,  1,  0,  0,  1,     -15,    0.0,     9,  0.0],
        [ -2,  0,  1,  0,  1,     -13,    0.0,     7,  0.0],
        [  0, -1,  0,  0,  1,     -12,    0.0,     6,  0.0],
        [  0,  0,  2, -2,  0,      11,    0.0,     0,  0.0],
        [  2,  0, -1,  2,  1,     -10,    0.0,     5,  0.0],
        [  2,  0,  1,  2,  2,      -8,    0.0,     3,  0.0],
        [  0,  1,  0,  2,  2,      -7,    0.0,     3,  0.0],
        [ -2,  1,  1,  0,  0,      -7,    0.0,     0,  0.0],
        [  0, -1,  0,  2,  2,      -7,    0.0,     3,  0.0],
        [  2,  0,  0,  2,  1,      -7,    0.0,     3,  0.0],
        [  0,  0,  1,  2,  0,       7,    0.0,     0,  0.0],
        [ -2,  0,  2,  2,  2,      -6,    0.0,     3,  0.0],
        [ -2,  0,  1,  2,  1,      -6,    0.0,     3,  0.0],
        [  2,  0, -2,  0,  1,       6,    0.0,    -3,  0.0],
        [  2,  0,  0,  0,  1,       6,    0.0,    -3,  0.0],
        [  0, -1,  1,  0,  0,       6,    0.0,     0,  0.0],
        [ -2, -1,  0,  2,  1,      -5,    0.0,     3,  0.0],
        [ -2,  0,  0,  0,  1,      -5,    0.0,     3,  0.0],
        [  0,  0,  2,  2,  1,      -5,    0.0,     3,  0.0],
        [ -2,  0,  2,  0,  1,       4,    0.0,     0,  0.0],
        [ -2,  1,  0,  2,  1,       4,    0.0,    -2,  0.0],
        [  0,  0,  1, -2,  0,       4,    0.0,     0,  0.0],
        [ -1,  0,  1,  0,  0,      -4,    0.0,     0,  0.0],
        [ -2,  1,  0,  0,  0,      -4,    0.0,     0,  0.0],
        [  1,  0,  0,  0,  0,      -4,    0.0,     0,  0.0],
        [  0,  0,  1,  2,  0,       3,    0.0,     0,  0.0],
        [  0,  0, -2,  2,  2,      -3,    0.0,     1,  0.0],
        [ -1, -1,  1,  0,  0,      -3,    0.0,     0,  0.0],
        [  0,  1,  1,  0,  0,      -3,    0.0,     0,  0.0],
        [  0, -1,  1,  2,  2,      -3,    0.0,     1,  0.0],
        [  2, -1, -1,  2,  2,      -3,    0.0,     1,  0.0],
        [  0,  0,  3,  2,  2,      -3,    0.0,     1,  0.0],
        [  2, -1,  0,  2,  2,      -3,    0.0,     1,  0.0],
    ];

    /**
     * Calculate nutation in longitude (Δψ) in degrees.
     */
    public function longitude(AstroInstant $instant): float
    {
        [$d, $m, $mp, $f, $om] = $this->fundamentalArguments($instant);
        $t = $instant->julianCenturiesTt();

        $deltaPsi = 0.0;

        foreach (self::TERMS as $row) {
            $arg   = $row[0] * $d + $row[1] * $m + $row[2] * $mp + $row[3] * $f + $row[4] * $om;
            $coeff = ($row[5] + $row[6] * $t) * 0.0001; // arcseconds
            $deltaPsi += $coeff * Angle::sin($arg);
        }

        return $deltaPsi / 3600.0; // convert arcseconds → degrees
    }

    /**
     * Calculate nutation in obliquity (Δε) in degrees.
     */
    public function obliquity(AstroInstant $instant): float
    {
        [$d, $m, $mp, $f, $om] = $this->fundamentalArguments($instant);
        $t = $instant->julianCenturiesTt();

        $deltaEps = 0.0;

        foreach (self::TERMS as $row) {
            $arg   = $row[0] * $d + $row[1] * $m + $row[2] * $mp + $row[3] * $f + $row[4] * $om;
            $coeff = ($row[7] + $row[8] * $t) * 0.0001; // arcseconds
            $deltaEps += $coeff * Angle::cos($arg);
        }

        return $deltaEps / 3600.0; // convert arcseconds → degrees
    }

    /**
     * Compute both Δψ and Δε in one pass for efficiency.
     *
     * @return array{0: float, 1: float}  [deltaPsi, deltaEps] in degrees
     */
    public function calculate(AstroInstant $instant): array
    {
        [$d, $m, $mp, $f, $om] = $this->fundamentalArguments($instant);
        $t = $instant->julianCenturiesTt();

        $deltaPsi = 0.0;
        $deltaEps = 0.0;

        foreach (self::TERMS as $row) {
            $arg = $row[0] * $d + $row[1] * $m + $row[2] * $mp + $row[3] * $f + $row[4] * $om;
            $argRad = $arg * 0.017453292519943295;
            $sinArg = sin($argRad);
            $cosArg = cos($argRad);

            $deltaPsi += ($row[5] + $row[6] * $t) * 0.0001 * $sinArg;
            $deltaEps += ($row[7] + $row[8] * $t) * 0.0001 * $cosArg;
        }

        return [$deltaPsi / 3600.0, $deltaEps / 3600.0];
    }

    /**
     * Fundamental arguments D, M, M′, F, Ω in degrees (normalised).
     *
     * @return array{0: float, 1: float, 2: float, 3: float, 4: float}
     */
    private function fundamentalArguments(AstroInstant $instant): array
    {
        $t = $instant->julianCenturiesTt();

        // Mean elongation of the Moon
        $d = Angle::normalize(
            Polynomial::evaluate($t, 297.85036, 445267.111480, -0.0019142, 1.0 / 189474.0)
        );

        // Mean anomaly of the Sun (Earth)
        $m = Angle::normalize(
            Polynomial::evaluate($t, 357.52772, 35999.050340, -0.0001603, -1.0 / 300000.0)
        );

        // Mean anomaly of the Moon
        $mp = Angle::normalize(
            Polynomial::evaluate($t, 134.96298, 477198.867398, 0.0086972, 1.0 / 56250.0)
        );

        // Moon's argument of latitude
        $f = Angle::normalize(
            Polynomial::evaluate($t, 93.27191, 483202.017538, -0.0036825, 1.0 / 327270.0)
        );

        // Longitude of the ascending node of the Moon's mean orbit
        $om = Angle::normalize(
            Polynomial::evaluate($t, 125.04452, -1934.136261, 0.0020708, 1.0 / 450000.0)
        );

        return [$d, $m, $mp, $f, $om];
    }
}
