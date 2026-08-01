<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Yoga\PanchaMahapurusha;

use Vittix\Panchang\Astrology\Yoga\YogaRuleInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Dignity;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\Yoga;

/**
 * Hamsa Yoga: Jupiter in a Kendra (1, 4, 7, 10 from Ascendant) and in its Own Sign or Exalted.
 */
final class HamsaYoga implements YogaRuleInterface
{
    public function detect(Kundali $kundali): ?Yoga
    {
        $jupiter = $kundali->placements[Body::Jupiter->value] ?? null;

        if ($jupiter === null) {
            return null;
        }

        $isKendra = in_array($jupiter->house, [1, 4, 7, 10], true);
        $isStrong = in_array($jupiter->dignity, [Dignity::Exalted, Dignity::Moolatrikona, Dignity::OwnSign], true);

        if ($isKendra && $isStrong) {
            return new Yoga(
                name: 'Hamsa Yoga',
                description: 'Jupiter is strong in an angular house. Brings wisdom, purity, religious/spiritual inclination, and respect in society.'
            );
        }

        return null;
    }
}
