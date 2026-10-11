<?php

declare(strict_types=1);

namespace Vittix\Panchang\Panchanga;

use RuntimeException;

/**
 * Thrown when the transition root finder cannot bracket or refine a limb
 * boundary within its iteration budget.
 *
 * Raised instead of silently returning an unrefined (hours-off) transition
 * time: callers can retry with a wider window or fall back to a different
 * computation strategy.
 */
final class TransitionSearchException extends RuntimeException
{
}
