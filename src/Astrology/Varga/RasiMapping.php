<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Varga;

use Vittix\Panchang\Enum\Rashi;

/**
 * Maps a longitude to the standard Rasi chart (D1).
 */
final class RasiMapping implements VargaMappingInterface
{
    public function getRashi(float $longitude): Rashi
    {
        return Rashi::fromIndex((int) floor($longitude / 30.0));
    }
}
