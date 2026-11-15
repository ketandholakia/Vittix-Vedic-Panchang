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
     * Stable, short identifier of this ayanamsha system
     * (e.g. 'lahiri', 'raman', 'krishnamurti').
     *
     * Used to namespace cached ayanamsha values so that two engines with
     * different systems sharing one PSR-16 cache cannot return each other's
     * values. Must contain no PSR-16 reserved characters: {}()/\@:
     */
    public function id(): string;

    /**
     * Return the Ayanamsha in decimal degrees for the given moment.
     */
    public function calculate(AstroInstant $instant): float;
}
