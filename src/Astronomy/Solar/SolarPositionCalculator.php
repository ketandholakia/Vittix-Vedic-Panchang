<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Solar;

use Vittix\Panchang\Astronomy\Coordinates\NutationCalculator;
use Vittix\Panchang\Astronomy\Coordinates\ObliquityCalculator;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Math\Polynomial;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\EclipticPosition;
use Vittix\Panchang\ValueObject\EquatorialPosition;

/**
 * Solar position calculator based on Meeus Chapter 25 (low-accuracy) and
 * the VSOP87 truncated series for higher accuracy when needed.
 *
 * Returns geocentric ecliptic coordinates. Accuracy of the apparent longitude
 * is better than 0.01° for 1900–2100.
 *
 * Reference: Jean Meeus, "Astronomical Algorithms", 2nd ed., Chapters 25–27.
 */
final class SolarPositionCalculator
{
    public function __construct(
        private readonly NutationCalculator  $nutation,
        private readonly ObliquityCalculator $obliquity,
    ) {
    }

    /**
     * Compute the full geocentric ecliptic position of the Sun.
     */
    public function calculate(AstroInstant $instant): EclipticPosition
    {
        $apparentLon = $this->apparentLongitude($instant);
        $latitude    = 0.0; // Sun's ecliptic latitude is essentially 0 by definition
        $distance    = $this->earthSunDistance($instant);

        return new EclipticPosition(
            longitude: $apparentLon,
            latitude: $latitude,
            distance: $distance,
        );
    }

    /**
     * Apparent geocentric ecliptic longitude of the Sun, in degrees [0, 360).
     *
     * Includes:
     *  • Geometric longitude (Meeus VSOP87 truncated)
     *  • Nutation in longitude (Δψ)
     *  • Aberration correction (−20.4898″ / R)
     */
    public function apparentLongitude(AstroInstant $instant): float
    {
        $t   = $instant->julianCenturiesTt();
        $geo = $this->geometricLongitude($t);

        // Nutation in longitude
        [$deltaPsi] = $this->nutation->calculate($instant);

        // Aberration: −20.4898″ / R (R = Earth–Sun distance in AU)
        $r = $this->earthSunDistance($instant);
        $aberration = -20.4898 / ($r * 3600.0); // degrees

        return Angle::normalize($geo + $deltaPsi + $aberration);
    }

    /**
     * Right ascension of the Sun in degrees [0, 360).
     */
    public function rightAscension(AstroInstant $instant): float
    {
        $pos = $this->calculate($instant);
        $eps = $this->obliquity->true($instant);

        $lon = $pos->longitude;

        $y = Angle::sin($lon) * Angle::cos($eps);
        $x = Angle::cos($lon);

        return Angle::normalize(Angle::atan2($y, $x));
    }

    /**
     * Declination of the Sun in degrees.
     */
    public function declination(AstroInstant $instant): float
    {
        $pos = $this->calculate($instant);
        $eps = $this->obliquity->true($instant);

        $sinDec = Angle::sin($pos->longitude) * Angle::sin($eps);

        return Angle::asin($sinDec);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Geometric mean ecliptic longitude of the Sun (Meeus VSOP87 truncated).
     * Accurate to better than 0.01° for 1900–2100.
     */
    private function geometricLongitude(float $t): float
    {
        // Mean longitude (L0) — degrees
        $l0 = Polynomial::evaluate($t, 280.46646, 36000.76983, 0.0003032);

        // Mean anomaly (M) — degrees
        $m = Angle::normalize(
            Polynomial::evaluate($t, 357.52911, 35999.05029, -0.0001537)
        );

        // Equation of centre
        $c = (1.914602 - 0.004817 * $t - 0.000014 * $t ** 2) * Angle::sin($m)
           + (0.019993 - 0.000101 * $t) * Angle::sin(2.0 * $m)
           +  0.000289 * Angle::sin(3.0 * $m);

        // Sun's true longitude
        $sunLon = $l0 + $c;

        // Apparent longitude: correct for nutation done separately in apparentLongitude()
        // Here we return the geometric value.
        return Angle::normalize($sunLon);
    }

    /**
     * Earth–Sun distance in AU.
     */
    private function earthSunDistance(AstroInstant $instant): float
    {
        $t = $instant->julianCenturiesTt();

        $m = Angle::normalize(
            Polynomial::evaluate($t, 357.52911, 35999.05029, -0.0001537)
        );

        // Eccentricity of Earth's orbit
        $e = Polynomial::evaluate($t, 0.016708634, -0.000042037, -0.0000001267);

        // Sun's anomaly
        $v = $m + (1.9146 - 0.004817 * $t - 0.000014 * $t ** 2) * Angle::sin($m)
               + (0.019993 - 0.000101 * $t) * Angle::sin(2.0 * $m)
               +  0.00029 * Angle::sin(3.0 * $m);

        return 1.000001018 * (1.0 - $e ** 2) / (1.0 + $e * Angle::cos($v));
    }
}
