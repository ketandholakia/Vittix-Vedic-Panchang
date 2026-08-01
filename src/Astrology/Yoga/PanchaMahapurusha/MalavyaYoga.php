<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Yoga\PanchaMahapurusha;

use Vittix\Panchang\Astrology\Yoga\YogaRuleInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Dignity;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\Yoga;

/**
 * Malavya Yoga: Venus in a Kendra (1, 4, 7, 10 from Ascendant) and in its Own Sign or Exalted.
 */
final class MalavyaYoga implements YogaRuleInterface
{
    public function detect(Kundali $kundali): ?Yoga
    {
        $venus = $kundali->placements[Body::Venus->value] ?? null;

        if ($venus === null) {
            return null;
        }

        $isKendra = in_array($venus->house, [1, 4, 7, 10], true);
        $isStrong = in_array($venus->dignity, [Dignity::Exalted, Dignity::Moolatrikona, Dignity::OwnSign], true);

        if ($isKendra && $isStrong) {
            return new Yoga(
                name: 'Malavya Yoga',
                description: 'Venus is strong in an angular house. Brings beauty, artistic talents, luxury, material wealth, and a happy married life.'
            );
        }

        return null;
    }
}
