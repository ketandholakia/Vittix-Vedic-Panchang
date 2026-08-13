<?php

declare(strict_types=1);

namespace Vittix\Panchang\Time;

final class PolynomialDeltaTProvider implements DeltaTProviderInterface
{
    public function getDeltaT(float $decimalYear): float
    {
        return DeltaT::forYear($decimalYear);
    }
}
