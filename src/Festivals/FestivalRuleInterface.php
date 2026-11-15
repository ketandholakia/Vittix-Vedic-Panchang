<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals;

/**
 * A rule that scores a festival against one civil date.
 *
 * The score is the fraction [0..1] of the rule's kala window during which
 * the target tithi prevails (in the target, non-Adhika lunar month), so
 * the festival engine can resolve each festival to the single best date
 * of the year. Rules return 0.0 when the festival is not observed on the
 * given date, when the lunar month is Adhika (skipped), or when the kala
 * window does not exist (polar day/night).
 */
interface FestivalRuleInterface
{
    /**
     * Observation score for the given day; 0.0 = not observed.
     */
    public function score(FestivalDayContext $context): float;
}
