<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals\Rules;

use Vittix\Panchang\Enum\Kala;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;

/**
 * A Viddha (vyapini) rule: the festival is observed when a specific tithi
 * prevails at any point of a specific Karmakala window (Madhyahna,
 * Aparahna, Pradosh, Nishita, …) in a specific lunar month.
 *
 * The score is the exact overlap of the tithi with the kala window
 * (boundaries located by bisection, real next sunrise), so a one-minute
 * overlap still resolves and the day with the greatest overlap wins.
 */
final class ViddhaTithiRule extends TithiKalaRule
{
    public function __construct(
        LunarMonth $targetMonth,
        Tithi $targetTithi,
        Kala $kala,
    ) {
        parent::__construct($targetMonth, $targetTithi, $kala);
    }
}
