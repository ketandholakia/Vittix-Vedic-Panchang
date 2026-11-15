<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals\Rules;

use Vittix\Panchang\Enum\Kala;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;

/**
 * A festival observed when a specific tithi prevails at sunrise in a
 * specific lunar month (classical sunrise-anchored rule).
 *
 * The kala window is the whole Panchang day [sunrise, next sunrise); days
 * where the tithi prevails at sunrise itself are preferred, and when the
 * tithi touches no sunrise the day with the greater overlap is chosen
 * (see {@see TithiKalaRule}). An optional alternative kala may be given.
 */
final class SunriseTithiRule extends TithiKalaRule
{
    public function __construct(
        LunarMonth $targetMonth,
        Tithi $targetTithi,
        Kala $kala = Kala::Sunrise,
    ) {
        parent::__construct($targetMonth, $targetTithi, $kala);
    }
}
