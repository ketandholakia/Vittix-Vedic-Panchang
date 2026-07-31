<?php

declare(strict_types=1);

namespace Vittix\Panchang\Panchanga;

use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Yoga as YogaEnum;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Yoga calculator.
 *
 * Yoga uses the *sum* of sidereal Sun and Moon longitudes:
 *   yogaLongitude = normalize(siderealSun + siderealMoon)
 *   index         = floor(yogaLongitude / (360/27))   [0–26]
 */
final class YogaCalculator
{
    private const SEGMENT = 360.0 / 27.0;

    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
    ) {
    }

    public function calculate(AstroInstant $instant): YogaEnum
    {
        $sun  = $this->astronomy->siderealLongitude(Body::Sun,  $instant);
        $moon = $this->astronomy->siderealLongitude(Body::Moon, $instant);

        return YogaEnum::fromIndex($this->index($sun, $moon));
    }

    public function index(float $siderealSun, float $siderealMoon): int
    {
        $sum = Angle::normalize($siderealSun + $siderealMoon);

        return (int) floor($sum / self::SEGMENT);
    }

    public function elapsedFraction(float $siderealSun, float $siderealMoon): float
    {
        $sum = Angle::normalize($siderealSun + $siderealMoon);

        return fmod($sum, self::SEGMENT) / self::SEGMENT;
    }
}
