<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * The system used to define the boundaries of a lunar month.
 */
enum MonthSystem: string
{
    /**
     * Amanta system (South India). The month begins after the New Moon (Amavasya)
     * and ends on the next New Moon.
     */
    case Amanta = 'amanta';

    /**
     * Purnimanta system (North India). The month begins after the Full Moon (Purnima)
     * and ends on the next Full Moon.
     */
    case Purnimanta = 'purnimanta';
}
