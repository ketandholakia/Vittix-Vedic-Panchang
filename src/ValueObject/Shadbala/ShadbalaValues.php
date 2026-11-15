<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject\Shadbala;

use Vittix\Panchang\Enum\Body;

/**
 * Holds the breakdown of the six-fold strength (Shadbala) for a single planet.
 * All values are in Virupas (1 Rupa = 60 Virupas).
 *
 * ⚠️ Some components may be partial/placeholder computations (e.g. Kala
 * Bala approximated by Ayana Bala alone, Drik Bala a 0 placeholder).
 * Check {@see isPartial()} per component before treating a value as a
 * full classical computation — the totals include partial components as
 * they are.
 */
final class ShadbalaValues
{
    /**
     * Component names ('sthana', 'dik', 'kala', 'chesta', 'naisargika',
     * 'drik') whose values are partial or placeholder computations.
     *
     * @var array<string, true>
     */
    public readonly array $partialComponents;

    /**
     * @param array<string, true> $partialComponents
     */
    public function __construct(
        public readonly Body $body,
        public readonly float $sthanaBala,
        public readonly float $dikBala,
        public readonly float $kalaBala,
        public readonly float $chestaBala,
        public readonly float $naisargikaBala,
        public readonly float $drikBala,
        array $partialComponents = [],
    ) {
        $this->partialComponents = $partialComponents;
    }

    /**
     * Whether the given component ('sthana', 'dik', 'kala', 'chesta',
     * 'naisargika', 'drik') is a partial/placeholder computation rather
     * than the full classical value.
     */
    public function isPartial(string $component): bool
    {
        return isset($this->partialComponents[$component]);
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
