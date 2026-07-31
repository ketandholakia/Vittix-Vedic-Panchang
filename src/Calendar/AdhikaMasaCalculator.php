<?php

declare(strict_types=1);

namespace Vittix\Panchang\Calendar;

use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Adhika Masa (intercalary / leap month) detection.
 *
 * An Adhika Masa occurs when the Sun remains in the same sidereal sign
 * (Rashi) for an entire lunar month — i.e. no Sankranti (solar-sign
 * transition) occurs between two consecutive new moons.
 *
 * This is the most common Adhika Masa rule (Amanta convention).
 * Kshaya Masa (lost month) is not handled in this version.
 *
 * Frequency: approximately once every 2.7 years.
 */
final class AdhikaMasaCalculator
{
    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
        private readonly LunarMonthCalculator     $lunarMonth,
    ) {
    }

    /**
     * Determine whether the lunation containing $instant is an Adhika Masa.
     *
     * Returns true if no solar sign transition occurs between the previous
     * new moon and the next new moon.
     */
    public function isAdhikaMasa(AstroInstant $instant): bool
    {
        $prevNm = $this->lunarMonth->previousNewMoon($instant);
        $nextNm = $this->lunarMonth->nextNewMoon($instant);

        $signAtPrev = $this->siderealSolarSign($prevNm);
        $signAtNext = $this->siderealSolarSign($nextNm);

        return $signAtPrev === $signAtNext;
    }

    /**
     * Find the Sankranti (solar-sign transition) time between two AstroInstants,
     * or return null if none occurs.
     */
    public function findSankranti(
        AstroInstant $from,
        AstroInstant $until
    ): ?AstroInstant {
        $signAt = fn (AstroInstant $i): int => $this->siderealSolarSign($i);

        $startSign = $signAt($from);
        $step      = 0.5; // 12-hour scan steps (days)

        $prevJd = $from->julianDayUt;
        $factory = $this->lunarMonth; // used only for its AstroTimeFactory via property

        // Inline factory use (avoid exposing it)
        $factory2 = new \Vittix\Panchang\Time\AstroTimeFactory();

        for (
            $jd = $from->julianDayUt + $step;
            $jd <= $until->julianDayUt;
            $jd += $step
        ) {
            $instant = $factory2->fromJulianDay($jd);

            if ($signAt($instant) !== $startSign) {
                return $this->bisectSankranti($prevJd, $jd, $startSign, $signAt);
            }

            $prevJd = $jd;
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function siderealSolarSign(AstroInstant $instant): int
    {
        $lon = $this->astronomy->siderealLongitude(Body::Sun, $instant);

        return (int) floor($lon / 30.0);
    }

    /**
     * @param callable(AstroInstant): int $signFn
     */
    private function bisectSankranti(
        float $lowerJd,
        float $upperJd,
        int $startSign,
        callable $signFn
    ): AstroInstant {
        $tolerance = 30.0 / 86400.0; // 30-second tolerance
        $factory   = new \Vittix\Panchang\Time\AstroTimeFactory();

        for ($i = 0; $i < 60; $i++) {
            $midJd   = ($lowerJd + $upperJd) / 2.0;
            $midInst = $factory->fromJulianDay($midJd);

            if ($signFn($midInst) === $startSign) {
                $lowerJd = $midJd;
            } else {
                $upperJd = $midJd;
            }

            if (($upperJd - $lowerJd) < $tolerance) {
                break;
            }
        }

        return $factory->fromJulianDay($upperJd);
    }
}
