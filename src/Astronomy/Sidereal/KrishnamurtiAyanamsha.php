<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Sidereal;

use Vittix\Panchang\Astronomy\Contracts\AyanamshaCalculatorInterface;
use Vittix\Panchang\Math\Polynomial;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Krishnamurti (KP) Ayanamsha.
 *
 * Propounded by K.S. Krishnamurti. The zero ayanamsha year is fixed at 291 AD.
 * It uses the standard precession rate similar to Lahiri but with a slightly
 * different starting epoch, resulting in a constant offset of roughly -0°05'
 * from Lahiri.
 *
 * Value at J2000.0 is approx 23°46'16.3" = 23.771194°.
 */
final class KrishnamurtiAyanamsha implements AyanamshaCalculatorInterface
{
    private const KP_J2000 = 23.771_194;

    public function calculate(AstroInstant $instant): float
    {
        $t = $instant->julianCenturiesTt(); // Julian centuries from J2000.0

        // KP Ayanamsha polynomial (same precession rate as standard IAU/Lahiri, 
        // just a different J2000 constant).
        $ayanamsha = Polynomial::evaluate(
            $t,
            self::KP_J2000,
            1.396_971,
            0.000_308,
            0.000_002
        );

        return $ayanamsha;
    }
}
