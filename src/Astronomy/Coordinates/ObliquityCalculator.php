<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Coordinates;

use Vittix\Panchang\Math\Polynomial;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Obliquity of the ecliptic (tilt of Earth's rotation axis).
 *
 * Mean obliquity from Laskar (1986) / IAU formula, true obliquity adds Δε.
 * Reference: Meeus, "Astronomical Algorithms" Ch. 22.
 */
final class ObliquityCalculator
{
    public function __construct(
        private readonly NutationCalculator $nutation,
    ) {
    }

    /**
     * Mean obliquity of the ecliptic in degrees (Laskar 1986).
     * Valid for several thousand years from J2000.
     */
    public function mean(AstroInstant $instant): float
    {
        $u = $instant->julianMillenniaTt();

        // Meeus Table 22.a — Laskar polynomial (arcseconds from 23°26′21.448″)
        $arcsec = Polynomial::evaluate(
            $u,
            0.0,          // constant — added below
            -4680.93,
            -1.55,
            1999.25,
            -51.38,
            -249.67,
            -39.05,
            7.12,
            27.87,
            5.79,
            2.45
        );

        // Base value: 23°26′21.448″ = 84381.448 arcseconds
        return (84381.448 + $arcsec) / 3600.0;
    }

    /**
     * True obliquity of the ecliptic (mean + Δε from nutation) in degrees.
     */
    public function true(AstroInstant $instant): float
    {
        [, $deltaEps] = $this->nutation->calculate($instant);

        return $this->mean($instant) + $deltaEps;
    }
}
