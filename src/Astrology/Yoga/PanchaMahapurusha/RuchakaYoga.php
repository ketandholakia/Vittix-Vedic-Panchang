<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Yoga\PanchaMahapurusha;

use Vittix\Panchang\Astrology\Yoga\YogaRuleInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Dignity;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\Yoga;

/**
 * Ruchaka Yoga: Mars in a Kendra (1, 4, 7, 10 from Ascendant) and in its Own Sign or Exalted.
 */
final class RuchakaYoga implements YogaRuleInterface
{
    public function detect(Kundali $kundali): ?Yoga
    {
        $mars = $kundali->placements[Body::Mars->value] ?? null;

        if ($mars === null) {
            return null;
        }

        $isKendra = in_array($mars->house, [1, 4, 7, 10], true);
        $isStrong = in_array($mars->dignity, [Dignity::Exalted, Dignity::Moolatrikona, Dignity::OwnSign], true);

        if ($isKendra && $isStrong) {
            return new Yoga(
                name: 'Ruchaka Yoga',
                description: 'Mars is strong in an angular house. Brings courage, leadership, physical strength, and success in police, military, or sports.'
            );
        }

        return null;
    }
}
