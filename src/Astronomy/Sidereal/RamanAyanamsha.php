<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Sidereal;

use Vittix\Panchang\Astronomy\Contracts\AyanamshaCalculatorInterface;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Raman Ayanamsha.
 *
 * Propounded by Dr. B.V. Raman. Assumes a zero ayanamsha year of 397 AD
 * and a constant rate of precession of 50.3333 seconds of arc per year.
 */
final class RamanAyanamsha implements AyanamshaCalculatorInterface
{
    public function calculate(AstroInstant $instant): float
    {
        $t = $instant->julianCenturiesTt(); // Julian centuries from J2000.0
        
        // Year relative to J2000.0
        // (J2000 corresponds to mid-day Jan 1, 2000. A tropical year is ~365.2422 days,
        // but simple century fraction is sufficient for Ayanamsha linear model.)
        $year = 2000.0 + ($t * 100.0);

        // Dr. Raman's formula: (Year - 397) * 50.3333 / 3600
        $ayanamsha = ($year - 397.0) * (50.3333 / 3600.0);

        return $ayanamsha;
    }
}
