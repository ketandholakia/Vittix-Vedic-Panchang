<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals\Rules;

use DateTimeImmutable;
use Vittix\Panchang\Calendar\Lunation;
use Vittix\Panchang\Enum\Kala;
use Vittix\Panchang\Enum\Karana as KaranaEnum;
use Vittix\Panchang\Enum\LunarMonth;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Festivals\FestivalDayContext;
use Vittix\Panchang\Festivals\FestivalRuleInterface;
use Vittix\Panchang\Panchanga\KaranaCalculator;
use Vittix\Panchang\ValueObject\TimeWindow;

/**
 * Raksha Bandhan (Shravana Purnima, nija Shravana only) — ordered tiers,
 * highest tier wins; within a tier the higher Bhadra-free overlap wins
 * (ties go to the later day):
 *
 *   1. Bhadra-free Purnima inside Aparahna.
 *   2. Else Bhadra-free Purnima inside Pradosh (sunset → +2 night-muhurtas).
 *   3. Else the civil date on which Purnima prevails after sunrise with
 *      Bhadra-free waking time (sunrise → midnight) before Purnima ends —
 *      typically the morning after a Bhadra-covered day.
 *
 * The waking-day restriction (sunrise → midnight) reflects drik's own
 * attribution: pre-dawn hours belong to the civil date they fall on (their
 * 2025 thread-ceremony muhurat 05:47–13:24 Aug 9 spans pre-dawn). Without
 * it, D0's score would absorb D1's morning and 2026 would resolve a day
 * early.
 *
 * Validated against drikpanchang 2023–2027 (New Delhi): all five dates
 * match — 2023-08-30 (tier 3; Bhadra ends 21:01, covers Aparahna and
 * Pradosh), 2024-08-19 (tier 1; Bhadra ends 13:30, before Aparahna),
 * 2025-08-09 / 2026-08-28 / 2027-08-17 (tier 3; "Bhadra got over before
 * Sunrise", Purnima ends 13:24 / 09:48 / 12:58). Adhika Shravana is
 * skipped: the festival belongs to nija Shravana only.
 */final class RakshaBandhanRule implements FestivalRuleInterface
{
    public function __construct(
        private readonly KaranaCalculator $karana,
    ) {
    }

    public function score(FestivalDayContext $context): float
    {
        $civil = Kala::Sunrise->window($context);
        if ($civil === null || $civil->end->getTimestamp() <= $civil->start->getTimestamp()) {
            return 0.0;
        }

        // Purnima and Vishti (Bhadra) segments across the civil day.
        $tithiSegments = $context->tithi->segmentsBetween($civil->start, $civil->end);
        $purnimaSegments = $this->segmentsOfIndex($tithiSegments, Tithi::Purnima->value);
        if ($purnimaSegments === []) {
            return 0.0;
        }

        // Month check at the Purnima span's midpoint (preverified-aware).
        $mid = $this->spanMidpoint($purnimaSegments, $civil);
        $lunation = $this->lunationAt($context, $mid);
        if ($lunation->month !== LunarMonth::Shravana || $lunation->isAdhika) {
            return 0.0;
        }

        $vishtiSegments = $this->vishtiSegments($context, $civil);

        // Bhadra-free Purnima = Purnima minus the Purnima∩Vishti
        // intersection, as a set of intervals.
        $bhadraFree = $this->subtractIntervals($purnimaSegments, $vishtiSegments);

        // The WAKING portion of the civil date: [sunrise(D), midnight(D)].
        // drik attributes pre-dawn hours to the civil date they fall on
        // (their 2025 muhurat 05:47–13:24 Aug 9 spans pre-dawn), so the
        // Bhadra-free Purnima that matters for the date choice is the part
        // between sunrise and midnight; the pre-midnight evening of D0 and
        // the post-sunrise morning of D1 are distinct, non-overlapping
        // windows and must not both count the same physical morning.
        $midnight = $context->date->modify('+1 day');
        $waking = new TimeWindow('Waking', $civil->start, $midnight);
        $wakingBhadraFree = $this->clipIntervals($bhadraFree, $waking);
        $wakingBhadraFreeSeconds = $this->secondsOfIntervals($wakingBhadraFree);

        $wakingSeconds = (float) ($midnight->getTimestamp() - $civil->start->getTimestamp());
        if ($wakingBhadraFreeSeconds <= 0) {
            // Bhadra covers all of the waking Purnima time on this date.
            return 0.0;
        }

        // Tier 1: Bhadra-free Purnima inside Aparahna.
        $aparahnas = Kala::Aparahna->window($context);
        if ($aparahnas !== null && $this->overlapSeconds($wakingBhadraFree, $aparahnas) > 0) {
            return 3.0 + $this->overlapSeconds($wakingBhadraFree, $aparahnas) / $wakingSeconds;
        }

        // Tier 2: Bhadra-free Purnima inside Pradosh (sunset + 2 night-muhurtas).
        $pradosh = Kala::Pradosh->window($context);
        if ($pradosh !== null && $this->overlapSeconds($wakingBhadraFree, $pradosh) > 0) {
            return 2.0 + $this->overlapSeconds($wakingBhadraFree, $pradosh) / $wakingSeconds;
        }

        // Tier 3: Purnima prevails after sunrise with Bhadra-free waking
        // time before Purnima ends (typically the morning after an
        // all-Bhadra previous day).
        return 1.0 + $wakingBhadraFreeSeconds / $wakingSeconds;
    }    /**
     * @param list<array{start: DateTimeImmutable, end: DateTimeImmutable, index: int}> $segments
     * @return list<array{start: DateTimeImmutable, end: DateTimeImmutable, index: int}>
     */
    private function segmentsOfIndex(array $segments, int $index): array
    {
        $out = [];
        foreach ($segments as $segment) {
            if ($segment['index'] === $index) {
                $out[] = $segment;
            }
        }

        return $out;
    }

    /**
     * Vishti (Bhadra) karana segments within the window. The karana index
     * (0–59, one per 6° of elongation) is located by the same boundary
     * bisection the tithi timeline uses.
     *
     * @return list<array{start: DateTimeImmutable, end: DateTimeImmutable}>
     */
    private function vishtiSegments(FestivalDayContext $context, TimeWindow $window): array
    {
        $indexAt = fn (DateTimeImmutable $moment): int => $this->karana->indexAt(
            $context->tithi->instantAt($moment)
        );

        $isVishti = static fn (int $index): bool => KaranaEnum::fromHalfTithiIndex($index)->isInauspicious();

        $segments = [];
        $cursor = $window->start;
        $index = $indexAt($cursor);

        while ($cursor->getTimestamp() < $window->end->getTimestamp()) {
            $boundary = $this->boundaryOfRunningIndex($indexAt, $cursor, $window->end, $index);

            if ($isVishti($index)) {
                $segments[] = ['start' => $cursor, 'end' => $boundary ?? $window->end];
            }

            if ($boundary === null) {
                break;
            }

            $cursor = $boundary;
            $index = ($index + 1) % 60;
        }

        return $segments;
    }

    /**
     * The instant within ($from, $until] at which the running karana ends
     * (null when it still runs at $until), located by bisection.
     *
     * @param callable(DateTimeImmutable): int $indexAt
     */
    private function boundaryOfRunningIndex(
        callable $indexAt,
        DateTimeImmutable $from,
        DateTimeImmutable $until,
        int $runningIndex
    ): ?DateTimeImmutable {
        if ($indexAt($until) === $runningIndex) {
            return null;
        }

        $lo = $from;
        $hi = $until;

        while ($hi->getTimestamp() - $lo->getTimestamp() > 1) {
            $midSeconds = intdiv($lo->getTimestamp() + $hi->getTimestamp(), 2);
            $mid = $lo->setTimestamp($midSeconds);

            if ($indexAt($mid) === $runningIndex) {
                $lo = $mid;
            } else {
                $hi = $mid;
            }
        }

        return $hi;
    }

    /**
     * @param list<array{start: DateTimeImmutable, end: DateTimeImmutable, index: int}> $segments
     */
    private function spanMidpoint(array $segments, TimeWindow $window): DateTimeImmutable
    {
        $first = $segments[0];
        $last = $segments[count($segments) - 1];

        $midSeconds = intdiv(
            $first['start']->getTimestamp() + $last['end']->getTimestamp(),
            2
        );

        return (new DateTimeImmutable())
            ->setTimestamp($midSeconds)
            ->setTimezone($window->start->getTimezone());
    }

    /**
     * @param list<array{start: DateTimeImmutable, end: DateTimeImmutable}> $intervals
     */
    private function secondsOfIntervals(array $intervals): float
    {
        $seconds = 0.0;
        foreach ($intervals as $interval) {
            $seconds += $interval['end']->getTimestamp() - $interval['start']->getTimestamp();
        }

        return $seconds;
    }

    /**
     * Seconds covered by both an interval list and a window.
     *
     * @param list<array{start: DateTimeImmutable, end: DateTimeImmutable}> $intervals
     */
    private function overlapSeconds(array $intervals, TimeWindow $window): float
    {
        $seconds = 0.0;
        foreach ($intervals as $interval) {
            $start = max($interval['start']->getTimestamp(), $window->start->getTimestamp());
            $end = min($interval['end']->getTimestamp(), $window->end->getTimestamp());
            if ($end > $start) {
                $seconds += $end - $start;
            }
        }

        return $seconds;
    }

    /**
     * Clip an interval list to a window.
     *
     * @param list<array{start: DateTimeImmutable, end: DateTimeImmutable}> $intervals
     * @return list<array{start: DateTimeImmutable, end: DateTimeImmutable}>
     */
    private function clipIntervals(array $intervals, TimeWindow $window): array
    {
        $out = [];
        foreach ($intervals as $interval) {
            $start = max($interval['start']->getTimestamp(), $window->start->getTimestamp());
            $end = min($interval['end']->getTimestamp(), $window->end->getTimestamp());
            if ($end > $start) {
                $out[] = [
                    'start' => $interval['start']->setTimestamp($start),
                    'end' => $interval['start']->setTimestamp($end),
                ];
            }
        }

        return $out;
    }

    /**
     * Purnima intervals minus Vishti intervals (both within the same
     * window, each sorted, ≤ 2 entries each).
     *
     * @param list<array{start: DateTimeImmutable, end: DateTimeImmutable, index: int}> $purnima
     * @param list<array{start: DateTimeImmutable, end: DateTimeImmutable}> $vishti
     * @return list<array{start: DateTimeImmutable, end: DateTimeImmutable}>
     */
    private function subtractIntervals(array $purnima, array $vishti): array
    {
        if ($vishti === []) {
            $out = [];
            foreach ($purnima as $segment) {
                $out[] = ['start' => $segment['start'], 'end' => $segment['end']];
            }

            return $out;
        }

        $out = [];
        foreach ($purnima as $segment) {
            $cursor = $segment['start'];
            foreach ($vishti as $v) {
                $vStart = max($v['start']->getTimestamp(), $cursor->getTimestamp());
                $vEnd = min($v['end']->getTimestamp(), $segment['end']->getTimestamp());
                if ($vEnd > $vStart) {
                    if ($vStart > $cursor->getTimestamp()) {
                        $out[] = ['start' => $cursor, 'end' => $cursor->setTimestamp($vStart)];
                    }
                    $cursor = $cursor->setTimestamp($vEnd);
                }
            }
            if ($segment['end']->getTimestamp() > $cursor->getTimestamp()) {
                $out[] = ['start' => $cursor, 'end' => $segment['end']];
            }
        }

        return $out;
    }

    private function lunationAt(FestivalDayContext $context, DateTimeImmutable $moment): Lunation
    {
        if ($context->preverifiedLunation !== null) {
            return $context->preverifiedLunation;
        }

        $instant = $context->tithi->instantAt($moment);

        return $context->lunarMonth->amantaLunation(
            $context->lunarMonth->previousNewMoon($instant)
        );
    }
}
