<?php

declare(strict_types=1);

namespace Vittix\Panchang\Panchanga;

use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Karana as KaranaEnum;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Karana calculator.
 *
 * Each Karana spans 6° of Moon–Sun elongation (half a Tithi = 30 Tithis × 2 = 60 Karanas).
 *
 *   halfTithiIndex = floor(elongation / 6)   [0–59]
 *
 * The mapping from index to Karana follows the fixed/repeating scheme in
 * KaranaEnum::fromHalfTithiIndex().
 */
final class KaranaCalculator
{
    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
    ) {
    }

    public function calculate(AstroInstant $instant): KaranaEnum
    {
        $sun  = $this->astronomy->tropicalLongitude(Body::Sun,  $instant);
        $moon = $this->astronomy->tropicalLongitude(Body::Moon, $instant);

        return KaranaEnum::fromHalfTithiIndex($this->halfTithiIndex($sun, $moon));
    }

    public function halfTithiIndex(float $sunLongitude, float $moonLongitude): int
    {
        $elongation = Angle::normalize($moonLongitude - $sunLongitude);

        return (int) floor($elongation / 6.0);
    }
}
