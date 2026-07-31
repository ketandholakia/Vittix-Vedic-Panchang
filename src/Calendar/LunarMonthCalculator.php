<?php

declare(strict_types=1);

namespace Vittix\Panchang\Calendar;

use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\Time\JulianDay;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Finds new moons and full moons, and derives the lunar month name.
 *
 * Algorithm:
 *   1. Find the previous new moon (Amavasya) before the query instant.
 *   2. Find the next new moon after the query instant.
 *   3. Determine the sidereal solar sign (Rashi) that the Sun enters between
 *      those two new moons.
 *   4. Map the solar sign to a lunar month name.
 *   5. If no solar-sign transition occurs in that lunation → Adhika Masa.
 *
 * New-moon definition: Moon–Sun tropical elongation = 0° (mod 360°).
 * The solver uses 30-minute steps followed by bisection.
 *
 * Reference: Meeus, "Astronomical Algorithms" Ch. 49; Dershowitz & Reingold
 * "Calendrical Calculations" Ch. 18.
 */
final class LunarMonthCalculator
{
    /** Scan step for bracketing a new/full moon (days). */
    private const SCAN_STEP = 0.5; // 12 hours

    /** Bisection tolerance in seconds. */
    private const TOLERANCE_SECONDS = 10.0;

    /** Maximum bisection iterations. */
    private const MAX_BISECT = 60;

    private readonly AstroTimeFactory $timeFactory;

    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
    ) {
        $this->timeFactory = new AstroTimeFactory();
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Find the previous new moon (Amavasya) before or at $from.
     */
    public function previousNewMoon(AstroInstant $from): AstroInstant
    {
        return $this->findPhase($from->julianDayUt, -1, PhaseTarget::NewMoon);
    }

    /**
     * Find the next new moon after $from.
     */
    public function nextNewMoon(AstroInstant $from): AstroInstant
    {
        return $this->findPhase($from->julianDayUt, +1, PhaseTarget::NewMoon);
    }

    /**
     * Find the previous full moon (Purnima) before or at $from.
     */
    public function previousFullMoon(AstroInstant $from): AstroInstant
    {
        return $this->findPhase($from->julianDayUt, -1, PhaseTarget::FullMoon);
    }

    /**
     * Find the next full moon after $from.
     */
    public function nextFullMoon(AstroInstant $from): AstroInstant
    {
        return $this->findPhase($from->julianDayUt, +1, PhaseTarget::FullMoon);
    }

    /**
     * Determine the Amanta lunar month for a given moment.
     *
     * Returns null if the month cannot be determined (should not happen in
     * practice for dates 1900–2100).
     */
    public function amantaMonth(AstroInstant $instant): LunarMonth
    {
        // Bracket: new moon before and after this moment
        $prevNm = $this->previousNewMoon($instant);
        $nextNm = $this->nextNewMoon($instant);

        // What sidereal solar sign does the Sun transit into during this lunation?
        $signAtPrevNm = $this->siderealSolarSign($prevNm);
        $signAtNextNm = $this->siderealSolarSign($nextNm);

        if ($signAtNextNm !== $signAtPrevNm) {
            // Normal month: Sun crosses a sign boundary → use the sign AFTER the crossing
            // The month name corresponds to the sign entered during the lunation.
            return $this->signToMonth($signAtNextNm);
        }

        // Adhika Masa: Sun stays in the same sign for the entire lunation.
        // The month is named after the *next* lunation's month.
        // To be defensive against boundary precision issues, search from 1 day after nextNm.
        $nextDay = $this->timeFactory->fromJulianDay($nextNm->julianDayUt + 1.0);
        $nextNextNm = $this->nextNewMoon($nextDay);
        $signAfter  = $this->siderealSolarSign($nextNextNm);

        return $this->signToMonth($signAfter);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Find the nearest new moon or full moon in a given direction.
     *
     * @param int          $direction +1 = forward, -1 = backward
     * @param PhaseTarget  $target    NewMoon (0°) or FullMoon (180°)
     */
    private function findPhase(
        float $startJd,
        int $direction,
        PhaseTarget $target
    ): AstroInstant {
        $targetElongation = $target->elongation();

        // If searching forward, we want strictly after.
        // If searching backward, we want before or at.
        // If the start point is already extremely close to the target phase, 
        // a forward search could detect the same boundary. Push by 1.0 days.
        $startElongation = $this->elongation($startJd);
        $distance = abs(Angle::normalizeSigned($startElongation - $targetElongation));
        
        // Moon moves ~12 degrees/day. If within 0.1 degree, we are very close to boundary.
        if ($direction > 0 && $distance < 0.1) {
            $startJd += 0.5; // push 12 hours forward
        }

        $prevJd     = $startJd;
        $prevElongation = $this->elongation($prevJd);

        $step = self::SCAN_STEP * $direction;

        for ($i = 0; $i < 100; $i++) {
            $currJd         = $prevJd + $step;
            $currElongation = $this->elongation($currJd);

            // Detect crossing of the target elongation
            if ($this->crossedTarget($prevElongation, $currElongation, $targetElongation)) {
                return $this->bisectPhase($prevJd, $currJd, $targetElongation);
            }

            $prevJd         = $currJd;
            $prevElongation = $currElongation;
        }

        // Fallback: return the start instant (should not reach here in valid date ranges)
        return $this->timeFactory->fromJulianDay($startJd);
    }

    /**
     * Moon–Sun tropical elongation, normalised to [0, 360).
     */
    private function elongation(float $jdUt): float
    {
        $instant = $this->timeFactory->fromJulianDay($jdUt);
        $sun     = $this->astronomy->tropicalLongitude(Body::Sun,  $instant);
        $moon    = $this->astronomy->tropicalLongitude(Body::Moon, $instant);

        return Angle::normalize($moon - $sun);
    }

    /**
     * Check if the elongation crossed the target boundary between two steps.
     *
     * Handles the 0°/360° wrap-around for new moon detection.
     */
    private function crossedTarget(
        float $prev,
        float $curr,
        float $target
    ): bool {
        // Normalize difference to handle wrapping
        $delta = Angle::normalizeSigned($curr - $prev);

        // Check if target falls in the interval (prev, prev + delta)
        $distPrev = Angle::normalizeSigned($target - $prev);

        return abs($delta) > 0.001
            && (($delta > 0 && $distPrev >= 0 && $distPrev < $delta)
                || ($delta < 0 && $distPrev <= 0 && $distPrev > $delta));
    }

    /**
     * Refine the phase time using bisection.
     */
    private function bisectPhase(
        float $lowerJd,
        float $upperJd,
        float $targetElongation
    ): AstroInstant {
        $tolerance = self::TOLERANCE_SECONDS / 86400.0;

        for ($i = 0; $i < self::MAX_BISECT; $i++) {
            $midJd  = ($lowerJd + $upperJd) / 2.0;
            $midElo = $this->elongation($midJd);

            $distMid  = Angle::normalizeSigned($midElo - $targetElongation);
            $distLow  = Angle::normalizeSigned($this->elongation($lowerJd) - $targetElongation);

            if ($distLow * $distMid <= 0) {
                $upperJd = $midJd;
            } else {
                $lowerJd = $midJd;
            }

            if (abs($upperJd - $lowerJd) < $tolerance) {
                break;
            }
        }

        return $this->timeFactory->fromJulianDay(($lowerJd + $upperJd) / 2.0);
    }

    /**
     * Sidereal solar sign (0=Mesha/Aries … 11=Meena/Pisces) at a given instant.
     * Uses floor(siderealSunLongitude / 30).
     */
    private function siderealSolarSign(AstroInstant $instant): int
    {
        $lon = $this->astronomy->siderealLongitude(Body::Sun, $instant);

        return (int) floor($lon / 30.0);
    }

    /**
     * Map a sidereal solar sign (0–11) to a LunarMonth.
     *
     * The mapping is:
     *   Sign 0 (Mesha/Aries)      → Chaitra  (month 1)
     *   Sign 1 (Vrishabha/Taurus) → Vaishakha
     *   …
     *   Sign 11 (Meena/Pisces)    → Phalguna
     */
    private function signToMonth(int $sign): LunarMonth
    {
        // Signs are 0-indexed from Mesha; months 1-indexed from Chaitra
        $monthNumber = ($sign % 12) + 1;

        return LunarMonth::from($monthNumber);
    }
}

/**
 * Internal enum for phase targets.
 * @internal
 */
enum PhaseTarget
{
    case NewMoon;
    case FullMoon;

    public function elongation(): float
    {
        return match ($this) {
            self::NewMoon  => 0.0,
            self::FullMoon => 180.0,
        };
    }
}
