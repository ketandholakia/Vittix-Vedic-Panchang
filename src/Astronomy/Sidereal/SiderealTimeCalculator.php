<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Sidereal;

use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Time\JulianDay;

/**
 * Greenwich and Local Mean Sidereal Time.
 *
 * Extracted from SolarEventCalculator, which needs GMST for the solar hour
 * angle at transit/sunrise/sunset. AscendantCalculator needs the same
 * quantity (as Local Sidereal Time, i.e. GMST + observer longitude) for the
 * houses/Lagna calculation, so this is factored out as a shared, stateless
 * utility rather than duplicated.
 *
 * This is *mean* sidereal time (no equation-of-equinoxes / nutation
 * correction). The omission is well under 1 second of time, which is
 * negligible for both sunrise/sunset (sub-second precision isn't
 * meaningful there either) and for Ascendant/house computation.
 *
 * Reference: Meeus, "Astronomical Algorithms", 2nd ed., Ch. 12, eq. 12.4.
 */
final class SiderealTimeCalculator
{
    /**
     * Greenwich Mean Sidereal Time in degrees [0, 360) for a given
     * Julian Day in Universal Time.
     */
    public function greenwichMeanSiderealTime(float $julianDayUt): float
    {
        $t = ($julianDayUt - JulianDay::J2000) / JulianDay::DAYS_PER_CENTURY;

        // GMST in degrees at the given UT instant (Meeus eq. 12.4).
        $gmst = 280.46061837
              + 360.98564736629 * ($julianDayUt - JulianDay::J2000)
              + 0.000387933 * $t ** 2
              - $t ** 3 / 38710000.0;

        return Angle::normalize($gmst);
    }

    /**
     * Local Mean Sidereal Time in degrees [0, 360) for a given Julian Day
     * in Universal Time and observer longitude (degrees, east-positive).
     */
    public function localMeanSiderealTime(float $julianDayUt, float $longitudeDeg): float
    {
        return Angle::normalize(
            $this->greenwichMeanSiderealTime($julianDayUt) + $longitudeDeg
        );
    }
}
