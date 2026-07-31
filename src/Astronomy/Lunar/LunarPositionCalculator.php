<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Lunar;

use Vittix\Panchang\Astronomy\Coordinates\NutationCalculator;
use Vittix\Panchang\Astronomy\Coordinates\ObliquityCalculator;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\EclipticPosition;

/**
 * Lunar position calculator using the Meeus ELP2000/82 truncated series.
 *
 * Implements the full 60-term longitude and latitude series from Meeus
 * Table 47.a and 47.b, plus Venus/Jupiter perturbations and flattening
 * corrections.
 *
 * Expected accuracy for apparent geocentric longitude: ≤ 10 arcseconds
 * (~0.003°) for 1950–2050. Latitude accuracy: ≤ 30 arcseconds (~0.008°).
 *
 * Tithi depends on Moon − Sun elongation, so errors in both bodies
 * partially cancel. Total Tithi longitude error is typically ≤ 0.05°,
 * corresponding to ≈ 4–5 minutes of Tithi transition uncertainty.
 *
 * Reference: Jean Meeus, "Astronomical Algorithms", 2nd ed., Chapter 47.
 */
final class LunarPositionCalculator
{
    public function __construct(
        private readonly LunarArgumentCalculator $arguments,
        private readonly NutationCalculator       $nutation,
        private readonly ObliquityCalculator      $obliquity,
    ) {
    }

    /**
     * Full geocentric ecliptic position of the Moon.
     *
     * @return EclipticPosition  longitude and latitude in degrees, distance in km.
     */
    public function calculate(AstroInstant $instant): EclipticPosition
    {
        $args = $this->arguments->calculate($instant);

        $d  = $args['d'];
        $m  = $args['m'];
        $mp = $args['mPrime'];
        $f  = $args['f'];
        $e  = $args['eccentricity'];
        $e2 = $e * $e;

        // --- Longitude ---
        $sigmaL = 0.0;
        $sigmaR = 0.0;

        foreach (LunarLongitudeTerms::TERMS as $row) {
            [$cd, $cm, $cmp, $cf, $sl, $sr] = $row;

            $arg    = $cd * $d + $cm * $m + $cmp * $mp + $cf * $f;
            $sinArg = Angle::sin($arg);
            $cosArg = Angle::cos($arg);

            // Eccentricity factor for terms involving M
            $eFactor = match (abs((int) $cm)) {
                1 => $e,
                2 => $e2,
                default => 1.0,
            };

            $sigmaL += $sl * $eFactor * $sinArg;
            $sigmaR += $sr * $eFactor * $cosArg;
        }

        // Additional longitude corrections (perturbations by Venus, Jupiter, and
        // the Moon's flattening) — Meeus p.338
        $sigmaL += 3958.0 * Angle::sin($args['a1'])
                +  1962.0 * Angle::sin($args['lPrime'] - $f)
                +   318.0 * Angle::sin($args['a2']);

        // --- Latitude ---
        $sigmaB = 0.0;

        foreach (LunarLatitudeTerms::TERMS as $row) {
            [$cd, $cm, $cmp, $cf, $sb] = $row;

            $arg    = $cd * $d + $cm * $m + $cmp * $mp + $cf * $f;
            $sinArg = Angle::sin($arg);

            $eFactor = match (abs((int) $cm)) {
                1 => $e,
                2 => $e2,
                default => 1.0,
            };

            $sigmaB += $sb * $eFactor * $sinArg;
        }

        // Additional latitude corrections (Meeus p.342)
        $sigmaB -= 2235.0 * Angle::sin($args['lPrime'])
                 +    382.0 * Angle::sin($args['a3'])
                 +    175.0 * Angle::sin($args['a1'] - $f)
                 +    175.0 * Angle::sin($args['a1'] + $f)
                 +    127.0 * Angle::sin($args['lPrime'] - $args['mPrime'])
                 -    115.0 * Angle::sin($args['lPrime'] + $args['mPrime']);

        // Geometric (mean) longitude + corrections → true longitude
        $longitude = $args['lPrime'] + ($sigmaL / 1_000_000.0);
        $latitude  = $sigmaB / 1_000_000.0;

        // Distance in km
        $distance  = 385000.56 + ($sigmaR / 1000.0);

        // Apply nutation in longitude for apparent longitude
        [$deltaPsi] = $this->nutation->calculate($instant);
        $apparentLongitude = Angle::normalize($longitude + $deltaPsi);

        return new EclipticPosition(
            longitude: $apparentLongitude,
            latitude: $latitude,
            distance: $distance,
        );
    }

    /**
     * Apparent geocentric ecliptic longitude of the Moon, in degrees [0, 360).
     */
    public function apparentLongitude(AstroInstant $instant): float
    {
        return $this->calculate($instant)->longitude;
    }
}
