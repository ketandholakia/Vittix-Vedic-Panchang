<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Solar;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\Time\JulianDay;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\ValueObject\SolarEvents;

/**
 * Iterative sunrise, solar noon, and sunset calculator.
 *
 * Algorithm:
 *  1. Estimate solar transit time (solar noon) at the observer's longitude.
 *  2. From the transit, estimate sunrise and sunset using the solar hour angle.
 *  3. Recalculate solar coordinates at the estimated event times.
 *  4. Iterate until the time changes by less than the tolerance.
 *
 * Convention used (matching the existing panchang-core package):
 *  • Standard solar altitude h₀ = −0.8333° (centre of Sun at apparent horizon,
 *    corrected for standard atmospheric refraction of 34′ and solar semi-
 *    diameter of 16′).
 *  • Upper limb of the Sun.
 *  • Elevation-dependent refraction correction is applied using a standard
 *    dip of 1.76′ × √(elevation in meters).
 *
 * Reference: Jean Meeus, "Astronomical Algorithms", 2nd ed., Ch. 15.
 */
final class SolarEventCalculator
{
    /** Standard solar altitude at sunrise/sunset (degrees). */
    private const H0 = -0.8333;

    /** Convergence tolerance in minutes. */
    private const TOLERANCE_MINUTES = 0.5;

    /** Maximum iterations. */
    private const MAX_ITER = 50;

    private readonly AstroTimeFactory $timeFactory;

    public function __construct(
        private readonly SolarPositionCalculator $solar
    ) {
        $this->timeFactory = new AstroTimeFactory();
    }

    /**
     * Calculate sunrise, solar noon, and sunset for a civil date and location.
     *
     * Returns null for individual events that do not occur (polar night /
     * midnight sun scenarios).
     */
    public function calculate(
        DateTimeImmutable $localDate,
        GeoLocation $location,
        DateTimeZone $timezone
    ): SolarEvents {
        // Meeus Ch 15: JD0 must be 0h UT of the date in question.
        // We take the local Y-m-d and treat it as 0h UTC to anchor the estimation.
        $midnight = new DateTimeImmutable(
            $localDate->format('Y-m-d 00:00:00'),
            new DateTimeZone('UTC')
        );

        $jd0 = JulianDay::fromUtc($midnight);

        // Approximate transit (solar noon) JD
        $transit = $this->estimateTransit($jd0, $location);

        // Approximate sunrise and sunset from transit
        [$sunrise, $sunset] = $this->estimateRiseset($transit, $location);

        // Iterate sunrise
        $sunriseJd  = null;
        $sunsetJd   = null;
        $solarNoonJd = null;

        if ($sunrise !== null) {
            $sunriseJd = $this->refineEvent($sunrise, $location, 'sunrise');
        }

        if ($sunset !== null) {
            $sunsetJd = $this->refineEvent($sunset, $location, 'sunset');
        }

        $solarNoonJd = $this->refineTransit($transit, $location);

        // Convert JD → local DateTimeImmutable
        return new SolarEvents(
            sunrise: $sunriseJd !== null
                ? JulianDay::toUtc($sunriseJd)->setTimezone($timezone)
                : null,
            solarNoon: JulianDay::toUtc($solarNoonJd)->setTimezone($timezone),
            sunset: $sunsetJd !== null
                ? JulianDay::toUtc($sunsetJd)->setTimezone($timezone)
                : null,
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Estimate transit JD (solar noon) from a Julian Day at midnight.
     */
    private function estimateTransit(float $jd0, GeoLocation $location): float
    {
        // Mean transit time is approximately 12:00 local solar time.
        // jd0 is 00:00 UTC of the target date.
        // jd0 + 0.5 is 12:00 UTC of the target date.
        // Subtracting longitude (in fractions of a day) yields local solar noon.
        return $jd0 + 0.5 - ($location->longitude / 360.0);
    }

    /**
     * Refine the transit (solar noon) time iteratively.
     */
    private function refineTransit(float $jdTransit, GeoLocation $location): float
    {
        for ($i = 0; $i < self::MAX_ITER; $i++) {
            $instant = $this->timeFactory->fromJulianDay($jdTransit);
            $ra      = $this->solar->rightAscension($instant);
            $lmst    = $this->greenwichSiderealTime($jdTransit) + $location->longitude;
            $lmst    = Angle::normalize($lmst);

            // Hour angle at transit = 0
            $correction = ($ra - $lmst) / 360.0; // days

            $newJd = $jdTransit + $correction;

            if (abs($newJd - $jdTransit) < self::TOLERANCE_MINUTES / (24.0 * 60.0)) {
                return $newJd;
            }

            $jdTransit = $newJd;
        }

        return $jdTransit;
    }

    /**
     * Estimate sunrise and sunset JDs from a transit JD.
     *
     * @return array{0: float|null, 1: float|null}  [sunrise JD, sunset JD]
     */
    private function estimateRiseset(float $jdTransit, GeoLocation $location): array
    {
        $instant = $this->timeFactory->fromJulianDay($jdTransit);
        $dec     = $this->solar->declination($instant);
        $lat     = $location->latitude;
        
        $h0Dip   = (1.76 / 60.0) * sqrt((float) max(0, $location->elevationMeters ?? 0));
        $effectiveH0 = self::H0 - $h0Dip;

        // cos(H₀) = (sin(h₀) − sin(φ)·sin(δ)) / (cos(φ)·cos(δ))
        $cosH0 = (Angle::sin($effectiveH0) - Angle::sin($lat) * Angle::sin($dec))
               / (Angle::cos($lat) * Angle::cos($dec));

        // Polar night or midnight sun
        if ($cosH0 < -1.0) {
            return [null, null]; // Midnight sun — no sunrise/sunset
        }

        if ($cosH0 > 1.0) {
            return [null, null]; // Polar night — Sun never rises
        }

        $h0    = Angle::acos($cosH0);              // degrees
        $h0Day = $h0 / 360.0;                       // fraction of day

        $sunriseJd = $jdTransit - $h0Day;
        $sunsetJd  = $jdTransit + $h0Day;

        return [$sunriseJd, $sunsetJd];
    }

    /**
     * Iteratively refine a sunrise or sunset Julian Day.
     *
     * @param string $type 'sunrise' or 'sunset'
     */
    private function refineEvent(float $jdEvent, GeoLocation $location, string $type): ?float
    {
        $lat = $location->latitude;

        for ($i = 0; $i < self::MAX_ITER; $i++) {
            $instant = $this->timeFactory->fromJulianDay($jdEvent);
            $ra      = $this->solar->rightAscension($instant);
            $dec     = $this->solar->declination($instant);

            $lmst = Angle::normalize(
                $this->greenwichSiderealTime($jdEvent) + $location->longitude
            );

            // Hour angle
            $h = Angle::normalize($lmst - $ra);
            // Convert to signed (-180…180)
            if ($h > 180.0) {
                $h -= 360.0;
            }

            // Altitude of the Sun at this hour angle
            $sinAlt = Angle::sin($lat) * Angle::sin($dec)
                    + Angle::cos($lat) * Angle::cos($dec) * Angle::cos($h);
            $altitude = Angle::asin($sinAlt);

            // Correction (Meeus eq. 15.5)
            $denom = Angle::cos($dec) * Angle::cos($lat) * Angle::sin($h);

            if (abs($denom) < 1e-10) {
                break; // Avoid division by zero near meridian
            }

            $h0Dip = (1.76 / 60.0) * sqrt((float) max(0, $location->elevationMeters ?? 0));
            $effectiveH0 = self::H0 - $h0Dip;

            $correction = ($altitude - $effectiveH0) / (360.0 * $denom);

            $newJd = $jdEvent + $correction;

            if (abs($newJd - $jdEvent) < self::TOLERANCE_MINUTES / (24.0 * 60.0)) {
                return $newJd;
            }

            $jdEvent = $newJd;
        }

        return $jdEvent;
    }

    /**
     * Apparent Greenwich Sidereal Time in degrees [0, 360).
     *
     * Uses the IAU formula for GMST + nutation (approximation without
     * a full IERS correction, sufficient for Panchang sunrise accuracy).
     *
     * Reference: Meeus Ch. 12.
     */
    private function greenwichSiderealTime(float $julianDayUt): float
    {
        $t = ($julianDayUt - JulianDay::J2000) / JulianDay::DAYS_PER_CENTURY;

        // GMST in degrees at 0h UT
        $gmst = 280.46061837
              + 360.98564736629 * ($julianDayUt - JulianDay::J2000)
              + 0.000387933 * $t ** 2
              - $t ** 3 / 38710000.0;

        return Angle::normalize($gmst);
    }
}
