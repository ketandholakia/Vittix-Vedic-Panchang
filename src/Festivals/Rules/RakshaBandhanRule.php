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
 * Raksha Bandhan (Shravana Purnima) — drik's Bhadra-aware convention.
 *
 * The festival is observed when Purnima prevails during Aparahna, EXCLUDING
 * the Vishti (Bhadra) karana: the thread is tied during the Bhadra-free
 * part of Aparahna. The score is the fraction of the Aparahna window that
 * is simultaneously Purnima and Vishti-free.
 *
 * Documented fallback: when Bhadra covers ALL of the Purnima time within
 * Aparahna (no Bhadra-free moment exists that day), drik still observes
 * the festival on that day — the thread is tied after Bhadra ends. Such a
 * day is therefore kept eligible at reduced weight (a quarter of its raw
 * overlap), so it resolves on its own yet loses to a Bhadra-free day if
 * one exists in the same lunation.
 *
 * Adhika Shravana is skipped: the festival belongs to nija Shravana only.
 */
final class RakshaBandhanRule implements FestivalRuleInterface
{
    public function __construct(
        private readonly KaranaCalculator $karana,
    ) {
    }

    public function score(FestivalDayContext $context): float
    {
        $window = Kala::Aparahna->window($context);
        if ($window === null || $window->end->getTimestamp() <= $window->start->getTimestamp()) {
            return 0.0;
        }

        $total = $window->end->getTimestamp() - $window->start->getTimestamp();

        $purnimaSegments = $this->segmentsOfIndex(
            $context->tithi->segmentsBetween($window->start, $window->end),
            Tithi::Purnima->value
        );
        if ($purnimaSegments === []) {
            return 0.0;
        }

        // Month check at the Purnima span's midpoint (preverified-aware).
        $mid = $this->spanMidpoint($purnimaSegments, $window);
        $lunation = $this->lunationAt($context, $mid);
        if ($lunation->month !== LunarMonth::Shravana || $lunation->isAdhika) {
            return 0.0;
        }

        $purnimaSeconds = $this->secondsOf($purnimaSegments);

        $vishtiSegments = $this->vishtiSegments($context, $window);
        $bhadraFreeSeconds = $purnimaSeconds
            - $this->intersectionSeconds($purnimaSegments, $vishtiSegments);

        if ($bhadraFreeSeconds > 0) {
            return $bhadraFreeSeconds / $total;
        }

        // Fallback: Bhadra covers all of the Purnima time within Aparahna —
        // keep the day eligible at reduced weight (see class docblock).
        return ($purnimaSeconds / $total) * 0.25;
    }

    /**
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
     * @param list<array{start: DateTimeImmutable, end: DateTimeImmutable, index: int}> $segments
     */
    private function secondsOf(array $segments): float
    {
        $seconds = 0.0;
        foreach ($segments as $segment) {
            $seconds += $segment['end']->getTimestamp() - $segment['start']->getTimestamp();
        }

        return $seconds;
    }

    /**
     * Seconds covered by both interval lists (each sorted, non-adjacent).
     *
     * @param list<array{start: DateTimeImmutable, end: DateTimeImmutable, index: int}> $a
     * @param list<array{start: DateTimeImmutable, end: DateTimeImmutable}> $b
     */
    private function intersectionSeconds(array $a, array $b): float
    {
        $seconds = 0.0;
        foreach ($a as $sa) {
            foreach ($b as $sb) {
                $start = max($sa['start']->getTimestamp(), $sb['start']->getTimestamp());
                $end = min($sa['end']->getTimestamp(), $sb['end']->getTimestamp());
                if ($end > $start) {
                    $seconds += $end - $start;
                }
            }
        }

        return $seconds;
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
