<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astronomy\Contracts;

use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Contract for Ayanamsha calculations.
 *
 * Ayanamsha is the angular difference between the tropical (Western/Sayana)
 * zero point and the sidereal (Hindu/Nirayana) zero point at a given epoch.
 *
 *   sidereal_longitude = tropical_longitude − ayanamsha
 *
 * The default and most commonly used value for Hindu Panchang is the
 * Lahiri (Chitrapaksha) Ayanamsha, adopted by the Government of India's
 * National Calendar Committee in 1955.
 */
interface AyanamshaCalculatorInterface
{
    /**
     * Return the Ayanamsha in decimal degrees for the given moment.
     */
    public function calculate(AstroInstant $instant): float;
}
