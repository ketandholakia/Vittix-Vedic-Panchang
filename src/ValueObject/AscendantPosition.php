<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

use Vittix\Panchang\Enum\Rashi;

/**
 * The Ascendant (Lagna) at a specific moment and location.
 *
 * Unlike planetary bodies, the Ascendant is not a physical object with a
 * geocentric ecliptic position — it is the point of the ecliptic rising on
 * the eastern horizon, which depends on both time and observer location.
 */
final readonly class AscendantPosition
{
    /**
     * @param float $tropicalLongitude Tropical ecliptic longitude in degrees [0, 360).
     * @param float $siderealLongitude Sidereal ecliptic longitude in degrees [0, 360),
     *                                 i.e. tropicalLongitude − ayanamsha.
     * @param Rashi $rashi             The sidereal zodiac sign (Lagna Rashi).
     * @param float $degreeInSign      Degrees elapsed within rashi, in [0, 30).
     */
    public function __construct(
        public readonly float $tropicalLongitude,
        public readonly float $siderealLongitude,
        public readonly Rashi $rashi,
        public readonly float $degreeInSign,
    ) {
    }
}
