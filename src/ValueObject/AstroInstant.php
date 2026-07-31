<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

use DateTimeImmutable;

/**
 * An immutable snapshot of a moment in time expressed in multiple time scales.
 *
 * Keeping UT and TT explicitly separated prevents one of the most common
 * astronomy implementation bugs, where the same Julian Day is used for both
 * coordinate formulae (which require TT) and sunrise/sunset calculations
 * (which require UT).
 *
 * All astronomical formulae that use Julian centuries T must use julianDayTt.
 * Sunrise/sunset and civil time outputs must use julianDayUt (and utc).
 */
final readonly class AstroInstant
{
    /**
     * @param DateTimeImmutable $utc             The moment in Universal Time (UTC).
     * @param float             $julianDayUt     Julian Day in Universal Time.
     * @param float             $julianDayTt     Julian Day in Terrestrial Time (TT = UT + ΔT).
     * @param float             $deltaTSeconds   ΔT in seconds (TT − UT).
     */
    public function __construct(
        public readonly DateTimeImmutable $utc,
        public readonly float $julianDayUt,
        public readonly float $julianDayTt,
        public readonly float $deltaTSeconds,
    ) {
    }

    /**
     * Julian centuries from J2000.0, using TT (for astronomical formulae).
     */
    public function julianCenturiesTt(): float
    {
        return ($this->julianDayTt - 2451545.0) / 36525.0;
    }

    /**
     * Julian millennia from J2000.0, using TT (for VSOP87 formulae).
     */
    public function julianMillenniaTt(): float
    {
        return ($this->julianDayTt - 2451545.0) / 365250.0;
    }
}
