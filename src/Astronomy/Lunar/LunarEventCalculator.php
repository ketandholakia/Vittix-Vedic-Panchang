<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Lunar;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Astronomy\Coordinates\CoordinateTransformer;
use Vittix\Panchang\Astronomy\Coordinates\ObliquityCalculator;
use Vittix\Panchang\Astronomy\Sidereal\SiderealTimeCalculator;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\Time\JulianDay;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\ValueObject\LunarEvents;

/**
 * Iterative moonrise, lunar transit, and moonset calculator.
 *
 * Civil-date semantics (the rule this calculator implements):
 *   • The moonrise (moonset) for a civil date D is the FIRST moonrise
 *     (moonset) whose local time falls within [00:00, 24:00) of D in the
 *     given timezone.
 *   • It is null only when the Moon does not rise (or set) at all during
 *     that local day. Because the Moon's transit-to-transit period averages
 *     ≈ 24 h 50 m, roughly one local day per lunation has no moonrise (or
 *     no moonset).
 *   • Internally the calculator scans a UTC window of at least 36 h around
 *     the local day (local midnight − 8 h … next local midnight + 8 h) with
 *     a 45-minute step, refines each horizon crossing by bisection, and
 *     then selects the first event belonging to the requested local date.
 *
 * The lunar transit keeps its historical meaning: the meridian passage
 * nearest 00:00 UT of the civil date.
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

    /** Coarse scan step in minutes for the horizon-crossing search. */
    private const SCAN_STEP_MINUTES = 45.0;

    /**
     * Fine scan step in minutes. Used to re-scan short intervals around
     * grazing extrema of the Moon's altitude, where two crossings can be
     * closer together than the coarse step (at high latitudes the Moon can
     * graze the horizon for only a handful of minutes).
     */
    private const FINE_SCAN_STEP_MINUTES = 5.0;

    /** Crossing refinement tolerance in seconds. */
    private const CROSSING_TOLERANCE_SECONDS = 2.0;

    /** Maximum bisection iterations per crossing. */
    private const MAX_CROSSING_ITERATIONS = 20;

    /** Buffer around the local day when scanning, in hours (window ≥ 36 h). */
    private const WINDOW_BUFFER_HOURS = 8.0;

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
     * Moonrise/moonset follow the civil-date rule documented on the class:
     * the first event whose local time falls in [00:00, 24:00) of the
     * requested date; null only when no such event exists.
     */
    public function calculate(
        DateTimeImmutable $localDate,
        GeoLocation $location,
        DateTimeZone $timezone
    ): LunarEvents {
        $dateKey = $localDate->format('Y-m-d');

        // Local day boundaries in universal time.
        $localMidnight = new DateTimeImmutable($dateKey . ' 00:00:00', $timezone);
        $nextMidnight = $localMidnight->modify('+1 day');

        $utc = new DateTimeZone('UTC');
        $windowStart = JulianDay::fromUtc($localMidnight->setTimezone($utc))
            - self::WINDOW_BUFFER_HOURS / 24.0;
        $windowEnd = JulianDay::fromUtc($nextMidnight->setTimezone($utc))
            + self::WINDOW_BUFFER_HOURS / 24.0;

        [$rises, $sets] = $this->findRiseSetInWindow($windowStart, $windowEnd, $location);

        $rises = self::dedupeEvents($rises);
        $sets = self::dedupeEvents($sets);

        $moonriseJd = $this->selectForLocalDate($rises, $dateKey, $timezone);
        $moonsetJd = $this->selectForLocalDate($sets, $dateKey, $timezone);

        // Lunar transit: meridian passage nearest 00:00 UT of the civil date.
        $midnightUtc = new DateTimeImmutable($dateKey . ' 00:00:00', $utc);
        $jd0 = JulianDay::fromUtc($midnightUtc);
        $transitJd = $this->refineTransit(
            $this->estimateTransit($jd0, $this->moonRA($jd0), $location),
            $location
        );

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
    // Rise/set search
    // -------------------------------------------------------------------------

    /**
     * Scan a UTC window for all moonrise and moonset events, ascending.
     *
     * The scan runs on a 45-minute grid. Because the Moon can graze the
     * horizon at high latitudes — with both crossings of a short window
     * falling between two grid samples — every three consecutive samples
     * additionally get a parabola check: the parabola through the samples
     * predicts the value at a grazing extremum, and whenever that prediction
     * would cross the horizon, the interval is re-scanned on a 5-minute grid
     * so the brief window is not missed. All crossings are then refined by
     * bisection.
     *
     * @return array{0: list<float>, 1: list<float>} [rise JDs, set JDs] (UT)
     */
    private function findRiseSetInWindow(float $fromJd, float $toJd, GeoLocation $location): array
    {
        $step = self::SCAN_STEP_MINUTES / 1440.0;
        $rises = [];
        $sets = [];

        /** @var float|null $prevPrevJd */
        $prevPrevJd = null;
        /** @var float|null $prevPrevAltitude */
        $prevPrevAltitude = null;
        $prevJd = $fromJd;
        $prevAltitude = $this->altitudeMinusH0($prevJd, $location);
        /** @var float|null $lastGrazingRescanEnd */
        $lastGrazingRescanEnd = null;

        for ($jd = $fromJd + $step; $jd <= $toJd; $jd += $step) {
            $altitude = $this->altitudeMinusH0($jd, $location);

            if ($prevAltitude < 0.0 && $altitude >= 0.0) {
                $rises[] = $this->refineCrossing($prevJd, $jd, true, $location);
            } elseif ($prevAltitude > 0.0 && $altitude <= 0.0) {
                $sets[] = $this->refineCrossing($prevJd, $jd, false, $location);
            } elseif ($prevPrevJd !== null && $prevPrevAltitude !== null) {
                $crossed = $this->grazingExtremumCrossesHorizon(
                    $prevPrevAltitude,
                    $prevAltitude,
                    $altitude
                );

                if ($crossed) {
                    $rescanStart = $lastGrazingRescanEnd === null
                        ? $prevPrevJd
                        : max($prevPrevJd, $lastGrazingRescanEnd);

                    if ($rescanStart < $jd) {
                        $this->scanIntervalFinely($rescanStart, $jd, $location, $rises, $sets);
                    }

                    $lastGrazingRescanEnd = $jd;
                }
            }

            $prevPrevJd = $prevJd;
            $prevPrevAltitude = $prevAltitude;
            $prevJd = $jd;
            $prevAltitude = $altitude;
        }

        return [$rises, $sets];
    }

    /**
     * Does the parabola through three equally spaced samples predict a
     * grazing extremum that crosses the horizon between the outer samples?
     *
     * A below-horizon window hides between three above-horizon samples when
     * the predicted vertex value is negative; an above-horizon window hides
     * between three below-horizon samples when it is positive.
     */
    private function grazingExtremumCrossesHorizon(
        float $first,
        float $middle,
        float $last
    ): bool {
        $allAbove = $first > 0.0 && $middle > 0.0 && $last > 0.0;
        $allBelow = $first < 0.0 && $middle < 0.0 && $last < 0.0;

        if (!$allAbove && !$allBelow) {
            return false;
        }

        $denominator = $first - 2.0 * $middle + $last;
        if (abs($denominator) < 1e-12) {
            return false;
        }

        // Vertex offset from the middle sample, in units of the sample spacing.
        $vertexOffset = ($first - $last) / (2.0 * $denominator);
        if (abs($vertexOffset) > 2.0) {
            return false;
        }

        $vertexValue = $middle - ($first - $last) ** 2 / (8.0 * $denominator);

        return $allAbove ? ($vertexValue < 0.0) : ($vertexValue > 0.0);
    }

    /**
     * Re-scan [startJd, endJd] on the fine grid and collect crossings.
     *
     * @param list<float> $rises
     * @param list<float> $sets
     */
    private function scanIntervalFinely(
        float $startJd,
        float $endJd,
        GeoLocation $location,
        array &$rises,
        array &$sets
    ): void {
        $step = self::FINE_SCAN_STEP_MINUTES / 1440.0;

        $prevJd = $startJd;
        $prevAltitude = $this->altitudeMinusH0($prevJd, $location);
        $jd = $startJd + $step;

        while (true) {
            $currentJd = min($jd, $endJd);
            $altitude = $this->altitudeMinusH0($currentJd, $location);

            if ($prevAltitude < 0.0 && $altitude >= 0.0) {
                $rises[] = $this->refineCrossing($prevJd, $currentJd, true, $location);
            } elseif ($prevAltitude > 0.0 && $altitude <= 0.0) {
                $sets[] = $this->refineCrossing($prevJd, $currentJd, false, $location);
            }

            if ($currentJd >= $endJd) {
                return;
            }

            $prevJd = $currentJd;
            $prevAltitude = $altitude;
            $jd += $step;
        }
    }

    /**
     * Collapse near-identical event times (possible when overlapping
     * parabola-checked intervals are re-scanned) and sort ascending.
     *
     * @param list<float> $events
     * @return list<float>
     */
    private static function dedupeEvents(array $events): array
    {
        sort($events);
        $result = [];
        foreach ($events as $event) {
            if ($result === [] || ($event - $result[count($result) - 1]) > 3.5e-5) {
                $result[] = $event;
            }
        }

        return $result;
    }

    /**
     * First event whose local calendar date equals the requested date.
     *
     * @param list<float> $events ascending event times (JD, UT)
     */
    private function selectForLocalDate(array $events, string $localDate, DateTimeZone $timezone): ?float
    {
        foreach ($events as $eventJd) {
            $local = JulianDay::toUtc($eventJd)->setTimezone($timezone);
            if ($local->format('Y-m-d') === $localDate) {
                return $eventJd;
            }
        }

        return null;
    }

    /**
     * Altitude of the Moon's centre above the apparent horizon, in degrees:
     *   alt(jd) − h₀,  h₀ = 0.7275·π − 34′
     * Positive when the Moon is up, negative when it is down.
     */
    private function altitudeMinusH0(float $jd, GeoLocation $location): float
    {
        $instant = $this->timeFactory->fromJulianDay($jd);
        $ecliptic = $this->lunarPosition->calculate($instant);
        $obliquity = $this->obliquity->true($instant);

        $transformer = new CoordinateTransformer();
        $equatorial = $transformer->eclipticToEquatorial($ecliptic, $obliquity);

        $distanceKm = $ecliptic->distance;
        $parallax = $distanceKm > 0.0 ? Angle::asin(6378.14 / $distanceKm) : 0.9508;
        $h0 = self::PARALLAX_FACTOR * $parallax - self::REFRACTION;

        $lmst = $this->siderealTime->localMeanSiderealTime($jd, $location->longitude);

        $hourAngle = Angle::normalize($lmst - $equatorial->rightAscension);
        if ($hourAngle > 180.0) {
            $hourAngle -= 360.0;
        }

        $sinAltitude = Angle::sin($location->latitude) * Angle::sin($equatorial->declination)
                     + Angle::cos($location->latitude) * Angle::cos($equatorial->declination)
                       * Angle::cos($hourAngle);
        $sinAltitude = max(-1.0, min(1.0, $sinAltitude));

        return Angle::asin($sinAltitude) - $h0;
    }

    /**
     * Bisect a bracketed horizon crossing to sub-2-second accuracy.
     *
     * The bracket [lo, hi] must straddle the crossing in the direction of
     * travel (rise: below → above; set: above → below). Returns an instant
     * on the "after" side of the crossing.
     */
    private function refineCrossing(float $loJd, float $hiJd, bool $isRise, GeoLocation $location): float
    {
        $tolerance = self::CROSSING_TOLERANCE_SECONDS / 86400.0;

        for ($i = 0; $i < self::MAX_CROSSING_ITERATIONS; $i++) {
            if (($hiJd - $loJd) <= $tolerance) {
                break;
            }

            $midJd = ($loJd + $hiJd) / 2.0;
            $midAltitude = $this->altitudeMinusH0($midJd, $location);
            $midPast = $isRise ? ($midAltitude >= 0.0) : ($midAltitude <= 0.0);

            if ($midPast) {
                $hiJd = $midJd;
            } else {
                $loJd = $midJd;
            }
        }

        return $hiJd;
    }

    // -------------------------------------------------------------------------
    // Lunar transit
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
     * Estimate transit JD (Moon crossing the observer's meridian), nearest
     * to the given anchor time.
     */
    private function estimateTransit(float $jd0, float $ra, GeoLocation $location): float
    {
        $gmst0 = $this->siderealTime->greenwichMeanSiderealTime($jd0);

        // Hour angle at the anchor time
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
        for ($i = 0; $i < 50; $i++) {
            $ra = $this->moonRA($jdTransit);
            $lmst = $this->siderealTime->localMeanSiderealTime($jdTransit, $location->longitude);

            // Hour angle should be 0 at transit
            $h = Angle::normalizeSigned($lmst - $ra);

            // Correction: account for Moon's own motion
            // Moon moves ~0.55°/hour in RA, so effective rate is ~14.49°/hour
            $correction = $h / 347.8; // days

            $newJd = $jdTransit + $correction;

            if (abs($newJd - $jdTransit) < 0.5 / (24.0 * 60.0)) {
                return $newJd;
            }

            $jdTransit = $newJd;
        }

        return $jdTransit;
    }
}
