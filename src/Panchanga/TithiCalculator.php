<?php

declare(strict_types=1);

namespace Vittix\Panchang\Panchanga;

use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Tithi as TithiEnum;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Tithi calculator.
 *
 * Tithi = the 30 lunar days obtained by dividing Moon–Sun elongation into
 * 12° segments.
 *
 *   elongation = normalize(moonLongitude − sunLongitude)
 *   index      = floor(elongation / 12)    [0–29]
 *
 * No Ayanamsha is needed because Tithi uses the *tropical* difference.
 */
final class TithiCalculator
{
    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
    ) {
    }

    /**
     * Calculate the Tithi at a given instant.
     */
    public function calculate(AstroInstant $instant): TithiEnum
    {
        $sun  = $this->astronomy->tropicalLongitude(Body::Sun,  $instant);
        $moon = $this->astronomy->tropicalLongitude(Body::Moon, $instant);

        return TithiEnum::fromIndex($this->index($sun, $moon));
    }

    /**
     * Return the 0-based Tithi index (0–29) for given sun and moon longitudes.
     */
    public function index(float $sunLongitude, float $moonLongitude): int
    {
        $elongation = Angle::normalize($moonLongitude - $sunLongitude);

        return (int) floor($elongation / 12.0);
    }

    /**
     * Elapsed fraction of the current Tithi (0.0 = just started, 1.0 = just ended).
     */
    public function elapsedFraction(float $sunLongitude, float $moonLongitude): float
    {
        $elongation = Angle::normalize($moonLongitude - $sunLongitude);

        return fmod($elongation, 12.0) / 12.0;
    }
}
