<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject\Shadbala;

/**
 * Contains the Shadbala values for all planets in a chart.
 */
final class Shadbala
{
    /**
     * @param array<int, ShadbalaValues> $planets Indexed by Body value (e.g. Body::Sun->value)
     */
    public function __construct(
        public readonly array $planets
    ) {
    }
}
