<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject\Shadbala;

use Vittix\Panchang\Enum\Body;

/**
 * Holds the breakdown of the six-fold strength (Shadbala) for a single planet.
 * All values are in Virupas (1 Rupa = 60 Virupas).
 */
final class ShadbalaValues
{
    public function __construct(
        public readonly Body $body,
        public readonly float $sthanaBala,
        public readonly float $dikBala,
        public readonly float $kalaBala,
        public readonly float $chestaBala,
        public readonly float $naisargikaBala,
        public readonly float $drikBala,
    ) {
    }

    /**
     * Get the total strength in Virupas.
     */
    public function getTotalVirupas(): float
    {
        return $this->sthanaBala + 
               $this->dikBala + 
               $this->kalaBala + 
               $this->chestaBala + 
               $this->naisargikaBala + 
               $this->drikBala;
    }

    /**
     * Get the total strength in Rupas (1 Rupa = 60 Virupas).
     */
    public function getTotalRupas(): float
    {
        return $this->getTotalVirupas() / 60.0;
    }
}
