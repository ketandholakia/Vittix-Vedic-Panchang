<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Coordinates;

use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\ValueObject\EclipticPosition;
use Vittix\Panchang\ValueObject\EquatorialPosition;

/**
 * Converts between ecliptic and equatorial coordinate systems.
 */
final class CoordinateTransformer
{
    /**
     * Convert geocentric ecliptic coordinates to equatorial (RA, Dec).
     *
     * @param EclipticPosition $pos       Ecliptic longitude and latitude in degrees.
     * @param float            $obliquity True obliquity of the ecliptic in degrees.
     */
    public function eclipticToEquatorial(
        EclipticPosition $pos,
        float $obliquity
    ): EquatorialPosition {
        $lon  = $pos->longitude;
        $lat  = $pos->latitude;
        $eps  = $obliquity;

        // Right ascension
        $y = Angle::sin($lon) * Angle::cos($eps) - Angle::tan($lat) * Angle::sin($eps);
        $x = Angle::cos($lon);

        $ra  = Angle::normalize(Angle::atan2($y, $x));

        // Declination
        $sinDec = Angle::sin($lat) * Angle::cos($eps)
                + Angle::cos($lat) * Angle::sin($eps) * Angle::sin($lon);
        $dec = Angle::asin($sinDec);

        return new EquatorialPosition($ra, $dec);
    }
}
