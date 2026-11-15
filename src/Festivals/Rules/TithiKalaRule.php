<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals\Rules;

use DateTimeImmutable;
use DateTimeZone;
use Vittix\Panchang\Calendar\Lunation;
use Vittix\Panchang\Enum\Kala;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Festivals\FestivalDayContext;
use Vittix\Panchang\Festivals\FestivalRuleInterface;
use Vittix\Panchang\Festivals\TithiTimeline;
use Vittix\Panchang\ValueObject\TimeWindow;

/**
 * Shared implementation for tithi-in-kala festival rules.
 *
 * A festival is observed when its target tithi prevails during its kala
 * window in the target lunar month. The score is the overlap fraction of
 * the target tithi with the kala window, computed across the window by
 * locating the exact tithi boundaries (bisection on the Moon–Sun
 * elongation) — never by sampling a single instant, and using the real
 * next sunrise. Adhika (intercalary) months are skipped: the festival
 * belongs to the nija lunation only.
 *
 * For the Sunrise kala the window is the whole Panchang day
 * [sunrise, next sunrise) and a day where the tithi prevails at sunrise
 * itself outranks every day where it merely overlaps (classical
 * "tithi at sunrise" convention with the greater-overlap fallback for
 * tithis that touch no sunrise — e.g. Gudi Padwa 2026-03-19).
 *
 * Razor-edge fallback: when the tithi overlaps no kala window at all
 * (e.g. Janmashtami 2026 at Ahmedabad — Ashtami ends 00:13 and the
 * Nishita muhurta begins 00:15), the two days adjacent to the tithi span
 * are scored negatively by their gap to it, so the engine still resolves
 * the single nearest day once no positive-overlap day exists. Zero means
 * the target tithi is neither inside nor adjacent to the day's window.
 */
abstract class TithiKalaRule implements FestivalRuleInterface
{
    protected function __construct(
        private readonly LunarMonth $targetMonth,
        private readonly Tithi $targetTithi,
        private readonly Kala $kala,
    ) {
    }

    final public function score(FestivalDayContext $context): float
    {
        $window = $this->kala->window($context);
        if ($window === null || $window->end->getTimestamp() <= $window->start->getTimestamp()) {
            return 0.0;
        }

        $timeline = $context->tithi;
        $target = $this->targetTithi->value;

        // Enumerate the tithi segments within the window (a window spans at
        // most three tithis: night/day windows are shorter than one tithi,
        // the sunrise day window can only cross two boundaries because
        // consecutive tithi durations sum to well over 24 hours).
        $segments = [];
        $cursor = $window->start;
        $index = $timeline->indexAt($window->start);
        while (true) {
            $boundary = $timeline->endOfRunningTithi($cursor, $window->end);
            if ($boundary === null) {
                $segments[] = [$cursor, $window->end, $index];
                break;
            }
            $segments[] = [$cursor, $boundary, $index];
            $cursor = $boundary;
            $index = ($index + 1) % 30;
        }

        $targetSegment = null;
        foreach ($segments as $segment) {
            if ($segment[2] === $target) {
                $targetSegment = $segment;
                break;
            }
        }

        if ($targetSegment === null) {
            return $this->adjacentGapScore($context, $timeline, $window, $target);
        }

        return $this->overlapScore($context, $timeline, $window, $targetSegment[0], $targetSegment[1]);
    }

    private function overlapScore(
        FestivalDayContext $context,
        TithiTimeline $timeline,
        TimeWindow $window,
        DateTimeImmutable $targetStart,
        DateTimeImmutable $targetEnd,
    ): float {
        $total = $window->end->getTimestamp() - $window->start->getTimestamp();
        $fraction = ($targetEnd->getTimestamp() - $targetStart->getTimestamp()) / $total;
        if ($fraction <= 0.0) {
            return 0.0;
        }

        // The month must match at the target tithi's own span (not merely at
        // sunrise): the lunation may change mid-day (new moon), and Shukla
        // Pratipada belongs to the lunation that begins at that new moon.
        $midSeconds = intdiv(
            $targetStart->getTimestamp() + $targetEnd->getTimestamp(),
            2
        );
        $mid = (new DateTimeImmutable())
            ->setTimestamp($midSeconds)
            ->setTimezone($window->start->getTimezone());

        $lunation = $this->lunationAt($context, $mid);
        if ($lunation->month !== $this->targetMonth || $lunation->isAdhika) {
            return 0.0;
        }

        // Sunrise tier: a tithi prevailing at sunrise outranks any day where
        // it merely overlaps the Panchang day.
        if ($this->kala === Kala::Sunrise && $timeline->indexAt($window->start) === $this->targetTithi->value) {
            $fraction += 1.0;
        }

        return $fraction;
    }

    /**
     * Negative gap score when the target tithi does not reach this day's
     * kala window but is adjacent to it (ends within a tithi-length before
     * the window, or starts within one after it).
     */
    private function adjacentGapScore(
        FestivalDayContext $context,
        TithiTimeline $timeline,
        TimeWindow $window,
        int $target,
    ): float {
        $firstIndex = $timeline->indexAt($window->start);
        $lastIndex = $timeline->indexAt($window->end);

        // Target ended just before the window: gap = window start − tithi end.
        if (($firstIndex - 1 + 30) % 30 === $target) {
            // The boundary is where the tithi running at the window start
            // BEGAN — search narrowly so the window contains exactly one
            // index transition (a 30h back-search could contain two).
            $boundary = $timeline->startOfRunningTithi(
                $window->start,
                $window->start->modify('-28 hours')
            );
            if ($boundary === null) {
                return 0.0;
            }

            return $this->gapScore($context, $boundary, $window, -1);
        }

        // Target starts just after the window: gap = tithi start − window end.
        if (($lastIndex + 1) % 30 === $target) {
            $boundary = $timeline->endOfRunningTithi(
                $window->end,
                $window->end->modify('+30 hours')
            );
            if ($boundary === null) {
                return 0.0;
            }

            return $this->gapScore($context, $boundary, $window, +1);
        }

        return 0.0;
    }

    /**
     * Score a zero-overlap day as −gap/86400 (gap in days). The month is
     * checked around the boundary instant, on the side where the target
     * tithi runs.
     */
    private function gapScore(
        FestivalDayContext $context,
        DateTimeImmutable $boundary,
        TimeWindow $window,
        int $targetSide, // -1: tithi ends at the boundary (before the window), +1: starts at it (after the window)
    ): float {
        $mid = $boundary->modify(($targetSide < 0 ? '-' : '+') . '12 hours');
        $lunation = $this->lunationAt($context, $mid);
        if ($lunation->month !== $this->targetMonth || $lunation->isAdhika) {
            return 0.0;
        }

        $gap = $targetSide < 0
            ? $window->start->getTimestamp() - $boundary->getTimestamp()
            : $boundary->getTimestamp() - $window->end->getTimestamp();

        return -abs($gap) / 86400.0;
    }

    private function lunationAt(FestivalDayContext $context, DateTimeImmutable $moment): Lunation
    {
        $instant = $context->tithi->instantAt($moment);

        return $context->lunarMonth->amantaLunation(
            $context->lunarMonth->previousNewMoon($instant)
        );
    }
}
