<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

/**
 * Represents a classical planetary combination (Yoga) detected in a birth chart.
 */
final class Yoga
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
    ) {
    }
}
