<?php

declare(strict_types=1);

namespace Vittix\Panchang\Math;

/**
 * Polynomial evaluation using Horner's method.
 *
 * Astronomical formulae frequently express corrections as polynomial series
 * in Julian centuries T.  Horner's method minimises the number of
 * multiplications and avoids catastrophic cancellation for moderate-degree
 * polynomials.
 *
 * Usage:
 *   Polynomial::evaluate($T, $a0, $a1, $a2, $a3)
 *   evaluates  a0 + a1·T + a2·T² + a3·T³
 */
final class Polynomial
{
    private function __construct() {}

    /**
     * Evaluate a polynomial at $x using Horner's method.
     *
     * Coefficients are ordered from lowest to highest power:
     *   coefficients[0]  → constant term (power 0)
     *   coefficients[1]  → coefficient of x   (power 1)
     *   coefficients[n]  → coefficient of x^n
     */
    public static function evaluate(float $x, float ...$coefficients): float
    {
        $n = count($coefficients);

        if ($n === 0) {
            return 0.0;
        }

        // Start from the highest-power coefficient and work down.
        $result = $coefficients[$n - 1];

        for ($i = $n - 2; $i >= 0; $i--) {
            $result = $result * $x + $coefficients[$i];
        }

        return $result;
    }
}
