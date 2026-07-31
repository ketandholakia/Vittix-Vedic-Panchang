<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * The two principal Hindu lunar calendar reckoning systems.
 *
 * Amanta  — month ends at Amavasya (new moon). Standard in South India,
 *            Gujarat, Maharashtra, and most modern civil Panchang.
 *
 * Purnimanta — month ends at Purnima (full moon). Standard in North India
 *              and the Purnimanta tradition. The Purnimanta month name is
 *              always one month ahead of the Amanta name in Krishna Paksha.
 */
enum CalendarSystem: string
{
    case Amanta     = 'amanta';
    case Purnimanta = 'purnimanta';
}
