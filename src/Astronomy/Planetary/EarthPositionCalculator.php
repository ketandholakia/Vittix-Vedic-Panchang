<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Planetary;

use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\Time\JulianDay;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Calculates Earth's heliocentric spherical coordinates using VSOP87.
 *
 * The term series are the VSOP87 "D" variant: geometric heliocentric
 * coordinates referred to the mean ecliptic and equinox of date.
 *
 * Returns heliocentric longitude, latitude, and distance (of date).
 */
final class EarthPositionCalculator
{
    /**
     * Compute Earth's heliocentric coordinates (L, B, R) at the given instant.
     *
     * @return float[] [Longitude (degrees), Latitude (degrees), Radius (AU)]
     */
    public function calculate(AstroInstant $instant): array
    {
        $t = ($instant->julianDayTt - JulianDay::J2000) / JulianDay::DAYS_PER_MILLENNIUM;

        $c = EarthTerms::class;
        $lSeries = [
            defined("$c::L0") ? constant("$c::L0") : [],
            defined("$c::L1") ? constant("$c::L1") : [],
            defined("$c::L2") ? constant("$c::L2") : [],
            defined("$c::L3") ? constant("$c::L3") : [],
            defined("$c::L4") ? constant("$c::L4") : [],
            defined("$c::L5") ? constant("$c::L5") : [],
        ];
        $bSeries = [
            defined("$c::B0") ? constant("$c::B0") : [],
            defined("$c::B1") ? constant("$c::B1") : [],
            defined("$c::B2") ? constant("$c::B2") : [],
            defined("$c::B3") ? constant("$c::B3") : [],
            defined("$c::B4") ? constant("$c::B4") : [],
            defined("$c::B5") ? constant("$c::B5") : [],
        ];
        $rSeries = [
            defined("$c::R0") ? constant("$c::R0") : [],
            defined("$c::R1") ? constant("$c::R1") : [],
            defined("$c::R2") ? constant("$c::R2") : [],
            defined("$c::R3") ? constant("$c::R3") : [],
            defined("$c::R4") ? constant("$c::R4") : [],
            defined("$c::R5") ? constant("$c::R5") : [],
        ];

        $l = $this->evaluateSeries($lSeries, $t);
        $b = $this->evaluateSeries($bSeries, $t);
        $r = $this->evaluateSeries($rSeries, $t);

        return [
            Angle::normalize(rad2deg($l)),
            rad2deg($b),
            $r
        ];
    }

    /**
     * @param array<int, array<int, array{float, float, float}>> $series
     */
    private function evaluateSeries(array $series, float $t): float
    {
        $sum = 0.0;
        $tPower = 1.0;

        foreach ($series as $terms) {
            $termSum = 0.0;
            foreach ($terms as [$amp, $phase, $freq]) {
                $termSum += $amp * cos($phase + $freq * $t);
            }
            $sum += $termSum * $tPower;
            $tPower *= $t;
        }

        return $sum;
    }
}

