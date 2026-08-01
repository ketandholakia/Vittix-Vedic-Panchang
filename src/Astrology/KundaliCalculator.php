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
        private readonly DignityCalculator $dignityCalculator,
        private readonly AspectCalculator $aspectCalculator,
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

            $dignity = $this->dignityCalculator->calculate($body, $rashi, $longitude);
            $aspectsHouses = $this->aspectCalculator->calculateAspectedHouses($body, $house);

            // Advanced Astronomy properties (used by Shadbala)
            $declination = null;
            $dailyMotion = null;

            // Rahu and Ketu usually don't need Declination/Speed for classical rules,
            // but we can compute them if they are true bodies. Our pure PHP engine
            // currently supports these for all bodies except Rahu/Ketu.
            if (!in_array($body, [Body::RahuMean, Body::KetuMean], true)) {
                $eq = $this->astronomy->equatorialPosition($body, $instant);
                $declination = $eq->declination;
                $dailyMotion = $this->astronomy->dailyMotion($body, $instant);
            }

            $placements[$body->value] = new PlanetPlacement(
                body: $body,
                longitude: $longitude,
                rashi: $rashi,
                house: $house,
                aspectsHouses: $aspectsHouses,
                dignity: $dignity,
                declination: $declination,
                dailyMotion: $dailyMotion,
            );
        }

        return new Kundali($ascendant, $placements);
    }
}
