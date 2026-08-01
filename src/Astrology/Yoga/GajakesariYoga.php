<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Yoga;

use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\Yoga;

/**
 * Gajakesari Yoga: Jupiter in a Kendra (1, 4, 7, 10) from the Moon.
 */
final class GajakesariYoga implements YogaRuleInterface
{
    public function detect(Kundali $kundali): ?Yoga
    {
        $moon = $kundali->placements[Body::Moon->value] ?? null;
        $jupiter = $kundali->placements[Body::Jupiter->value] ?? null;

        if ($moon === null || $jupiter === null) {
            return null;
        }

        // Distance in signs counting inclusive (1-based)
        $distance = ($jupiter->rashi->value - $moon->rashi->value + 12) % 12 + 1;

        if (in_array($distance, [1, 4, 7, 10], true)) {
            return new Yoga(
                name: 'Gajakesari Yoga',
                description: 'Jupiter is in a Kendra (angular position) from the Moon. This brings intelligence, prosperity, and respect.'
            );
        }

        return null;
    }
}
