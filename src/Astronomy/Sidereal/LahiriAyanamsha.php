<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Sidereal;

use Vittix\Panchang\Astronomy\Contracts\AyanamshaCalculatorInterface;
use Vittix\Panchang\Math\Polynomial;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Lahiri (Chitrapaksha) Ayanamsha.
 *
 * This is the Ayanamsha recommended by the Government of India's Calendar
 * Reform Committee (1955) and is the most widely used in Indian Panchang.
 *
 * The formula is based on the IAU standard tropical year and the adopted
 * position of the vernal equinox of 285 AD as the reference epoch
 * (equinox = Spica, Chitra star, at exactly 0° sidereal longitude).
 *
 * Lahiri's own revised formula (1985) is used here:
 *
 *   ayanamsha ≈ 22°27′32″ at J1900.0 (≈ 22.4589°)
 *   Rate of precession ≈ 50.2910″/year (≈ 1.397°/century)
 *
 * This implementation follows the polynomial form commonly cited in
 * astronomical software, calibrated so that at J2000.0 the Lahiri
 * Ayanamsha ≈ 23.8583° (≈ 23°51′30″).
 */
final class LahiriAyanamsha implements AyanamshaCalculatorInterface
{
    /**
     * Lahiri Ayanamsha at J2000.0 in degrees.
     * Source: Lahiri, "Indian Ephemeris" series; Dershowitz & Reingold Ch. 18.
     */
    private const LAHIRI_J2000 = 23.853_749_1;

    public function calculate(AstroInstant $instant): float
    {
        $t = $instant->julianCenturiesTt(); // Julian centuries from J2000.0

        // Lahiri polynomial (more precise version using T from J2000.0)
        // Based on B. V. Raman / Lahiri standard widely used by ephemeris software:
        //   ayanamsha(T) = 23.853749 + 1.396971 * T + 0.000308 * T² + 0.000002 * T³
        // where T is Julian centuries from J2000.0
        $ayanamsha = Polynomial::evaluate(
            $t,
            self::LAHIRI_J2000,
            1.396_971,
            0.000_308,
            0.000_002
        );

        return $ayanamsha;
    }
}
