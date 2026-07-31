<?php

declare(strict_types=1);

namespace Vittix\Panchang\Panchanga;

use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Janmarashi calculator.
 *
 * Janmarashi is the Moon's sidereal zodiac sign (Rashi) at a given instant.
 * In practice this is the birth Moon sign used in many Hindu astrological
 * calculations.
 */
final class JanmarashiCalculator
{
    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
    ) {
    }

    public function calculate(AstroInstant $instant): Rashi
    {
        return Rashi::fromIndex($this->index($instant));
    }

    public function index(AstroInstant $instant): int
    {
        $siderealMoon = $this->astronomy->siderealLongitude(Body::Moon, $instant);

        return (int) floor(Angle::normalize($siderealMoon) / 30.0);
    }
}
