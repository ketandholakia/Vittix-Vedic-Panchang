<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Rashi;

/**
 * Represents a complete Kundali (Birth Chart) containing the Ascendant and planetary placements.
 */
final class Kundali
{
    /**
     * @param AscendantPosition $ascendant The Lagna (Ascendant) details.
     * @param array<value-of<Body>, PlanetPlacement> $placements Map of Body enums to their placements.
     */
    public function __construct(
        public readonly AscendantPosition $ascendant,
        public readonly array $placements,
    ) {
    }

    /**
     * Returns an array of PlanetPlacements that are situated in the given house.
     *
     * @param int $house 1-12
     * @return array<PlanetPlacement>
     */
    public function getPlacementsInHouse(int $house): array
    {
        return array_filter(
            $this->placements,
            fn(PlanetPlacement $p) => $p->house === $house
        );
    }

    /**
     * Returns an array of PlanetPlacements that are situated in the given Rashi.
     *
     * @return array<PlanetPlacement>
     */
    public function getPlacementsInRashi(Rashi $rashi): array
    {
        return array_filter(
            $this->placements,
            fn(PlanetPlacement $p) => $p->rashi === $rashi
        );
    }
}
