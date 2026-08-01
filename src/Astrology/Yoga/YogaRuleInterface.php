<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology\Yoga;

use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\Yoga;

/**
 * Interface for classical planetary yoga detection rules.
 */
interface YogaRuleInterface
{
    /**
     * Checks the Kundali for the presence of this specific Yoga.
     * 
     * @return Yoga|null Returns the Yoga object if detected, or null if not present.
     */
    public function detect(Kundali $kundali): ?Yoga;
}
