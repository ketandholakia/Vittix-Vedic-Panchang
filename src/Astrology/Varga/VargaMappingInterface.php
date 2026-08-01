<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Varga;

use Vittix\Panchang\Enum\Rashi;

/**
 * Interface for calculating a planet's mapped Rashi in a specific Divisional Chart.
 */
interface VargaMappingInterface
{
    /**
     * Get the mapped Rashi for a given sidereal longitude.
     */
    public function getRashi(float $longitude): Rashi;
}
