<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Yoga\PanchaMahapurusha;

use Vittix\Panchang\Astrology\Yoga\YogaRuleInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Dignity;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\Yoga;

/**
 * Sasha Yoga: Saturn in a Kendra (1, 4, 7, 10 from Ascendant) and in its Own Sign or Exalted.
 */
final class SashaYoga implements YogaRuleInterface
{
    public function detect(Kundali $kundali): ?Yoga
    {
        $saturn = $kundali->placements[Body::Saturn->value] ?? null;

        if ($saturn === null) {
            return null;
        }

        $isKendra = in_array($saturn->house, [1, 4, 7, 10], true);
        $isStrong = in_array($saturn->dignity, [Dignity::Exalted, Dignity::Moolatrikona, Dignity::OwnSign], true);

        if ($isKendra && $isStrong) {
            return new Yoga(
                name: 'Sasha Yoga',
                description: 'Saturn is strong in an angular house. Brings longevity, endurance, authority in masses or politics, and success through hard work.'
            );
        }

        return null;
    }
}
