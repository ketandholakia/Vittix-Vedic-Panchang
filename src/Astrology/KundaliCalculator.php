<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology;

use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Astronomy\Coordinates\AscendantCalculator;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Rashi;
use Vittix\Panchang\Math\Angle;
use Vittix\Panchang\ValueObject\AstroInstant;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\PlanetPlacement;

/**
 * Assembles a Kundali (Birth Chart) using the Whole-Sign house system.
 */
final class KundaliCalculator
{
    private const BODIES_TO_CALCULATE = [
        Body::Sun,
        Body::Moon,
        Body::Mars,
        Body::Mercury,
        Body::Jupiter,
        Body::Venus,
        Body::Saturn,
        Body::RahuMean,
        Body::KetuMean,
    ];

    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
        private readonly AscendantCalculator $ascendantCalculator,
    ) {
    }

    public function calculate(AstroInstant $instant, GeoLocation $location): Kundali
    {
        $ascendant = $this->ascendantCalculator->calculate($instant, $location);
        $lagnaRashiIndex = $ascendant->rashi->value;

        $placements = [];

        foreach (self::BODIES_TO_CALCULATE as $body) {
            $longitude = Angle::normalize($this->astronomy->siderealLongitude($body, $instant));
            $rashiIndex = (int) floor($longitude / 30.0);
            $rashi = Rashi::fromIndex($rashiIndex);

            // Whole-Sign House Calculation
            $house = (($rashiIndex - $lagnaRashiIndex + 12) % 12) + 1;

            $placements[$body->value] = new PlanetPlacement(
                body: $body,
                longitude: $longitude,
                rashi: $rashi,
                house: $house
            );
        }

        return new Kundali($ascendant, $placements);
    }
}
