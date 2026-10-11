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
 * Kshaya Masa (lost month) detection lives with the lunar month logic.
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

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function siderealSolarSign(AstroInstant $instant): int
    {
        $lon = $this->astronomy->siderealLongitude(Body::Sun, $instant);

        return (int) floor($lon / 30.0);
    }
}
