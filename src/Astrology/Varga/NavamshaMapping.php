<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Varga;

use Vittix\Panchang\Enum\Rashi;

/**
 * Maps a longitude to the Navamsha chart (D9).
 */
final class NavamshaMapping implements VargaMappingInterface
{
    public function getRashi(float $longitude): Rashi
    {
        // Navamsha is 3 degrees 20 minutes = 3.3333333 degrees
        $navamshaLength = 30.0 / 9.0;
        
        // Find the absolute navamsha index (0 to 107)
        $navamshaIndex = (int) floor($longitude / $navamshaLength);

        // Map it back to a Rashi (mod 12)
        return Rashi::fromIndex($navamshaIndex % 12);
    }
}
