<?php

declare(strict_types=1);

namespace Vittix\Panchang\Time;

interface DeltaTProviderInterface
{
    /**
     * Calculate Delta T (in seconds) for a given decimal year.
     *
     * Delta T is the difference between Terrestrial Time (TT) and Universal Time (UT).
     */
    public function getDeltaT(float $decimalYear): float;
}
