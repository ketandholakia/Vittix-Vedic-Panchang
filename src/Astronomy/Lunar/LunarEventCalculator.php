<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Lunar;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Astronomy\Coordinates\CoordinateTransformer;
use Vittix\Panchang\Astronomy\Coordinates\ObliquityCalculator;
use Vittix\Panchang\Astronomy\Sidereal\SiderealTimeCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\Time\JulianDay;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\ValueObject\LunarEvents;

/**
 * Iterative moonrise, lunar transit, and moonset calculator.
 *
 * Unlike solar events, the Moon's large horizontal parallax (~0.95°) and
 * rapid daily motion (~13.2°/day in longitude) require topocentric
 * corrections for accurate rise/set times.
 *
 * Algorithm:
 *  1. Estimate lunar transit from the Moon's RA and observer longitude.
 *  2. Estimate moonrise/moonset using the Moon's hour angle at the
 *     apparent horizon altitude.
 *  3. Recalculate the Moon's equatorial coordinates at the estimated
 *     event times and iterate until convergence.
 *
 * Apparent horizon altitude for the Moon:
 *   h₀ = 0.7275 × π − 0°34′
 * where π is the Moon's horizontal parallax. For mean parallax (57′02″),
 * h₀ ≈ +0.125°. This is positive (unlike the Sun's −0.8333°) because
 * parallax lifts the Moon above the geometric horizon more than refraction
 * depresses it.
 *
 * Reference: Jean Meeus, "Astronomical Algorithms", 2nd ed., Ch. 15.
 */
final class LunarEventCalculator
{
    /** Standard atmospheric refraction at the horizon (degrees). */
    private const REFRACTION = 34.0 / 60.0; // 34 arcminutes

    /** Moon's angular radius coefficient. */
    private const PARALLAX_FACTOR = 0.7275;

    /** Convergence tolerance in minutes. */
    private const TOLERANCE_MINUTES = 0.5;

    /** Maximum iterations per event. */
    private const MAX_ITER = 50;

    private readonly AstroTimeFactory $timeFactory;
    private readonly SiderealTimeCalculator $siderealTime;

    public function __construct(
        private readonly LunarPositionCalculator $lunarPosition,
        private readonly ObliquityCalculator $obliquity,
        ?SiderealTimeCalculator $siderealTime = null,
    ) {
        $this->timeFactory = new AstroTimeFactory();
        $this->siderealTime = $siderealTime ?? new SiderealTimeCalculator();
    }

    /**
     * Calculate moonrise, lunar transit, and moonset for a civil date and location.
     *
     * The Moon's synodic period exceeds 24 hours, so on some days there may be
     * no moonrise or no moonset. These are returned as null.
     */
    public function calculate(
        DateTimeImmutable $localDate,
        GeoLocation $location,
        DateTimeZone $timezone
    ): LunarEvents {
        // Anchor: 0h UT of the target civil date
        $midnight = new DateTimeImmutable(
            $localDate->format('Y-m-d 00:00:00'),
            new DateTimeZone('UTC')
        );
        $jd0 = JulianDay::fromUtc($midnight);

        // Get Moon's equatorial coordinates at 0h, 12h, 24h for interpolation
        $ra0  = $this->moonRA($jd0);
        $dec0 = $this->moonDec($jd0);
        $hp0  = $this->moonHorizontalParallax($jd0);

        // Estimate transit (lunar meridian passage)
        $transitJd = $this->estimateTransit($jd0, $ra0, $location);

        // Get Moon's parallax at transit for the h₀ calculation
        $hp = $this->moonHorizontalParallax($transitJd);
        $h0 = self::PARALLAX_FACTOR * $hp - self::REFRACTION;

        // Estimate rise/set from transit
        [$riseJd, $setJd] = $this->estimateRiseset($transitJd, $location, $h0);

        // Refine transit
        $transitJd = $this->refineTransit($transitJd, $location);

        // Refine moonrise
        $moonriseJd = null;
        if ($riseJd !== null) {
            $moonriseJd = $this->refineEvent($riseJd, $location, 'rise');
        }

        // Refine moonset
        $moonsetJd = null;
        if ($setJd !== null) {
            $moonsetJd = $this->refineEvent($setJd, $location, 'set');
        }

        // Validate: events should be within ±1 day of the target date
        $dayStart = $jd0;
        $dayEnd   = $jd0 + 1.5; // allow generous window for moonset after midnight

        if ($moonriseJd !== null && ($moonriseJd < $dayStart - 0.5 || $moonriseJd > $dayEnd)) {
            $moonriseJd = null;
        }
        if ($moonsetJd !== null && ($moonsetJd < $dayStart - 0.5 || $moonsetJd > $dayEnd)) {
            $moonsetJd = null;
        }

        return new LunarEvents(
            moonrise: $moonriseJd !== null
                ? JulianDay::toUtc($moonriseJd)->setTimezone($timezone)
                : null,
            transit: JulianDay::toUtc($transitJd)->setTimezone($timezone),
            moonset: $moonsetJd !== null
                ? JulianDay::toUtc($moonsetJd)->setTimezone($timezone)
                : null,
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Moon's geocentric right ascension in degrees at a given JD.
     */
    private function moonRA(float $jd): float
    {
        $instant = $this->timeFactory->fromJulianDay($jd);
        $ecliptic = $this->lunarPosition->calculate($instant);
        $obl = $this->obliquity->true($instant);

        $transformer = new CoordinateTransformer();
        $eq = $transformer->eclipticToEquatorial($ecliptic, $obl);

        return $eq->rightAscension;
    }

    /**
     * Moon's geocentric declination in degrees at a given JD.
     */
    private function moonDec(float $jd): float
    {
        $instant = $this->timeFactory->fromJulianDay($jd);
        $ecliptic = $this->lunarPosition->calculate($instant);
        $obl = $this->obliquity->true($instant);

        $transformer = new CoordinateTransformer();
        $eq = $transformer->eclipticToEquatorial($ecliptic, $obl);

        return $eq->declination;
    }

    /**
     * Moon's horizontal parallax in degrees at a given JD.
     *
     * π = arcsin(6378.14 / distance_km)
     * Mean value ≈ 57′02″ ≈ 0.9508°
     */
    private function moonHorizontalParallax(float $jd): float
    {
        $instant = $this->timeFactory->fromJulianDay($jd);
        $pos = $this->lunarPosition->calculate($instant);

        // Moon distance is in km in our EclipticPosition
        $distanceKm = $pos->distance;

        if ($distanceKm <= 0.0) {
            return 0.9508; // fallback to mean parallax
        }

        return Angle::asin(6378.14 / $distanceKm);
    }

    /**
     * Estimate transit JD (Moon crossing the observer's meridian).
     */
    private function estimateTransit(float $jd0, float $ra, GeoLocation $location): float
    {
        $gmst0 = $this->siderealTime->greenwichMeanSiderealTime($jd0);

        // Hour angle at 0h UT
        $h = Angle::normalize($gmst0 + $location->longitude - $ra);
        if ($h > 180.0) {
            $h -= 360.0;
        }

        // The Moon moves about 13.2°/day relative to the stars, so the
        // sidereal day for the Moon is ~24h 50m. Correct for this.
        // Transit fraction of day = H / (360.0 + daily_motion_correction)
        // Use 360.9856° (sidereal rate) minus ~13.2° (Moon's RA motion) ≈ 347.8°
        $transitFraction = $h / 347.8;

        return $jd0 + $transitFraction;
    }

    /**
     * Refine the transit time iteratively.
     */
    private function refineTransit(float $jdTransit, GeoLocation $location): float
    {
        for ($i = 0; $i < self::MAX_ITER; $i++) {
            $ra = $this->moonRA($jdTransit);
            $lmst = $this->siderealTime->localMeanSiderealTime($jdTransit, $location->longitude);

            // Hour angle should be 0 at transit
            $h = Angle::normalizeSigned($lmst - $ra);

            // Correction: account for Moon's own motion
            // Moon moves ~0.55°/hour in RA, so effective rate is ~14.49°/hour
            $correction = $h / 347.8; // days

            $newJd = $jdTransit + $correction;

            if (abs($newJd - $jdTransit) < self::TOLERANCE_MINUTES / (24.0 * 60.0)) {
                return $newJd;
            }

            $jdTransit = $newJd;
        }

        return $jdTransit;
    }

    /**
     * Estimate moonrise and moonset JDs from a transit JD.
     *
     * @return array{0: float|null, 1: float|null} [rise JD, set JD]
     */
    private function estimateRiseset(float $jdTransit, GeoLocation $location, float $h0): array
    {
        $dec = $this->moonDec($jdTransit);
        $lat = $location->latitude;

        // cos(H₀) = (sin(h₀) − sin(φ)·sin(δ)) / (cos(φ)·cos(δ))
        $denominator = Angle::cos($lat) * Angle::cos($dec);

        if (abs($denominator) < 1e-10) {
            return [null, null];
        }

        $cosH0 = (Angle::sin($h0) - Angle::sin($lat) * Angle::sin($dec))
               / $denominator;

        // Moon never rises or never sets at this latitude
        if ($cosH0 < -1.0 || $cosH0 > 1.0) {
            return [null, null];
        }

        $hourAngle = Angle::acos($cosH0); // degrees

        // Convert to fraction of day, accounting for Moon's motion
        // Use effective rate ~347.8°/day instead of 360°
        $h0Day = $hourAngle / 347.8;

        $riseJd = $jdTransit - $h0Day;
        $setJd  = $jdTransit + $h0Day;

        return [$riseJd, $setJd];
    }

    /**
     * Iteratively refine a moonrise or moonset Julian Day.
     *
     * @param string $type 'rise' or 'set'
     */
    private function refineEvent(float $jdEvent, GeoLocation $location, string $type): float
    {
        $lat = $location->latitude;

        for ($i = 0; $i < self::MAX_ITER; $i++) {
            $instant = $this->timeFactory->fromJulianDay($jdEvent);
            $ecliptic = $this->lunarPosition->calculate($instant);
            $obl = $this->obliquity->true($instant);
            $transformer = new CoordinateTransformer();
            $eq = $transformer->eclipticToEquatorial($ecliptic, $obl);
            $ra  = $eq->rightAscension;
            $dec = $eq->declination;

            // Get parallax at this moment
            $hp = $this->moonHorizontalParallax($jdEvent);
            $h0 = self::PARALLAX_FACTOR * $hp - self::REFRACTION;

            $lmst = $this->siderealTime->localMeanSiderealTime($jdEvent, $location->longitude);

            // Hour angle
            $h = Angle::normalize($lmst - $ra);
            if ($h > 180.0) {
                $h -= 360.0;
            }

            // Altitude of the Moon at this hour angle
            $sinAlt = Angle::sin($lat) * Angle::sin($dec)
                    + Angle::cos($lat) * Angle::cos($dec) * Angle::cos($h);
            $altitude = Angle::asin($sinAlt);

            // Correction
            $denom = Angle::cos($dec) * Angle::cos($lat) * Angle::sin($h);

            if (abs($denom) < 1e-10) {
                break; // Avoid division by zero near meridian
            }

            // Use effective daily rate for Moon (347.8° instead of 360°)
            $correction = ($altitude - $h0) / (347.8 * $denom);

            $newJd = $jdEvent + $correction;

            if (abs($newJd - $jdEvent) < self::TOLERANCE_MINUTES / (24.0 * 60.0)) {
                return $newJd;
            }

            $jdEvent = $newJd;
        }

        return $jdEvent;
    }
}
