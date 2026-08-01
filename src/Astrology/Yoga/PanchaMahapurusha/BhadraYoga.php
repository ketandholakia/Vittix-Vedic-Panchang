<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Yoga\PanchaMahapurusha;

use Vittix\Panchang\Astrology\Yoga\YogaRuleInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Dignity;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\Yoga;

/**
 * Bhadra Yoga: Mercury in a Kendra (1, 4, 7, 10 from Ascendant) and in its Own Sign or Exalted.
 */
final class BhadraYoga implements YogaRuleInterface
{
    public function detect(Kundali $kundali): ?Yoga
    {
        $mercury = $kundali->placements[Body::Mercury->value] ?? null;

        if ($mercury === null) {
            return null;
        }

        $isKendra = in_array($mercury->house, [1, 4, 7, 10], true);
        $isStrong = in_array($mercury->dignity, [Dignity::Exalted, Dignity::Moolatrikona, Dignity::OwnSign], true);

        if ($isKendra && $isStrong) {
            return new Yoga(
                name: 'Bhadra Yoga',
                description: 'Mercury is strong in an angular house. Brings high intelligence, communication skills, business acumen, and a youthful appearance.'
            );
        }

        return null;
    }
}
