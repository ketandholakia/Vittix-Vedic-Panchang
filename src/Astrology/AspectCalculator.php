<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology;

use Vittix\Panchang\Enum\Body;

/**
 * Calculates which houses a planet aspects based on its current house.
 */
final class AspectCalculator
{
    /**
     * Get the absolute houses (1-12) that are aspected by the given planet
     * from its current house.
     *
     * @return int[]
     */
    public function calculateAspectedHouses(Body $body, int $currentHouse): array
    {
        $offsets = $this->getAspectOffsets($body);

        $aspectedHouses = [];
        foreach ($offsets as $offset) {
            $aspectedHouse = (($currentHouse + $offset - 2) % 12) + 1;
            $aspectedHouses[] = $aspectedHouse;
        }

        // Sort for predictable output
        sort($aspectedHouses);
        return $aspectedHouses;
    }

    /**
     * @return int[] The relative house offsets (e.g. [7] or [4, 7, 8])
     */
    private function getAspectOffsets(Body $body): array
    {
        return match ($body) {
            Body::Mars => [4, 7, 8],
            Body::Jupiter, Body::RahuMean, Body::KetuMean => [5, 7, 9],
            Body::Saturn => [3, 7, 10],
            default => [7],
        };
    }
}
