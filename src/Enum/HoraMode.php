<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * Hora division schemes for {@see \Vittix\Panchang\Muhurta\MuhurtaCalculator::getHoras()}.
 */
enum HoraMode
{
    /**
     * Traditional: 12 day Horas divide sunrise → sunset, 12 night Horas
     * divide sunset → next sunrise (each is 1/12 of its half, not a clock
     * hour).
     */
    case Traditional;

    /**
     * Legacy: 24 equal segments of the full sunrise → next-sunrise span.
     */
    case EqualHours;
}
