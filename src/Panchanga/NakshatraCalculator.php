<?php

declare(strict_types=1);

namespace Vittix\Panchang\Panchanga;

use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Nakshatra as NakshatraEnum;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Nakshatra and Pada calculator.
 *
 * Nakshatra uses *sidereal* Moon longitude.
 *   segment  = 360° / 27 = 13.333...°
 *   index    = floor(siderealMoon / segment)   [0–26]
 *
 * Pada (quarter) uses the same longitude divided into 108 equal parts:
 *   padaSegment = 360° / 108 = 3.333...°
 *   pada        = (floor(siderealMoon / padaSegment) % 4) + 1   [1–4]
 */
final class NakshatraCalculator
{
    private const SEGMENT = 360.0 / 27.0;      // ≈ 13.3333°
    private const PADA_SEGMENT = 360.0 / 108.0; // ≈ 3.3333°

    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
    ) {
    }

    /**
     * Calculate the Nakshatra at a given instant.
     */
    public function calculate(AstroInstant $instant): NakshatraEnum
    {
        $siderealMoon = $this->astronomy->siderealLongitude(Body::Moon, $instant);

        return NakshatraEnum::fromIndex($this->index($siderealMoon));
    }

    /**
     * Calculate the Pada (1–4) at a given instant.
     */
    public function pada(AstroInstant $instant): int
    {
        $siderealMoon = $this->astronomy->siderealLongitude(Body::Moon, $instant);

        return $this->padaFromLongitude($siderealMoon);
    }

    /**
     * 0-based Nakshatra index from sidereal Moon longitude.
     */
    public function index(float $siderealMoonLongitude): int
    {
        return (int) floor(Angle::normalize($siderealMoonLongitude) / self::SEGMENT);
    }

    /**
     * Pada (1–4) from sidereal Moon longitude.
     */
    public function padaFromLongitude(float $siderealMoonLongitude): int
    {
        $padaIndex = (int) floor(Angle::normalize($siderealMoonLongitude) / self::PADA_SEGMENT);

        return ($padaIndex % 4) + 1;
    }

    /**
     * Elapsed fraction of the current Nakshatra.
     */
    public function elapsedFraction(float $siderealMoonLongitude): float
    {
        return fmod(Angle::normalize($siderealMoonLongitude), self::SEGMENT) / self::SEGMENT;
    }
}
