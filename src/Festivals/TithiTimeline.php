<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals;

use DateTimeImmutable;
use Vittix\Panchang\Astronomy\Contracts\AstronomyEngineInterface;
use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Panchanga\TithiCalculator;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Continuous access to the tithi timeline around given instants.
 *
 * The Moon–Sun elongation strictly increases, so the tithi index
 * (elongation ÷ 12°, mod 30) increases monotonically through a day.
 * Boundaries are located by bisection on the index rather than by
 * approximations like "+1 day".
 */
final class TithiTimeline
{
    private readonly AstroTimeFactory $timeFactory;

    public function __construct(
        private readonly AstronomyEngineInterface $astronomy,
        private readonly TithiCalculator $tithiCalculator,
    ) {
        $this->timeFactory = new AstroTimeFactory();
    }

    public function instantAt(DateTimeImmutable $moment): AstroInstant
    {
        return $this->timeFactory->fromDateTime($moment);
    }

    /**
     * The tithi index (0–29) at the given moment.
     */
    public function indexAt(DateTimeImmutable $moment): int
    {
        $instant = $this->instantAt($moment);
        $sun = $this->astronomy->tropicalLongitude(Body::Sun, $instant);
        $moon = $this->astronomy->tropicalLongitude(Body::Moon, $instant);

        return $this->tithiCalculator->index($sun, $moon);
    }

    /**
     * The tithi segments covering [$from, $until]: a list of
     * {start, end, index} runs whose union is exactly the window (the
     * tithi index strictly increases from segment to segment).
     *
     * @return list<array{start: DateTimeImmutable, end: DateTimeImmutable, index: int}>
     */
    public function segmentsBetween(DateTimeImmutable $from, DateTimeImmutable $until): array
    {
        if ($until->getTimestamp() <= $from->getTimestamp()) {
            return [];
        }

        $segments = [];
        $cursor = $from;
        $index = $this->indexAt($from);

        while (true) {
            $boundary = $this->endOfRunningTithi($cursor, $until);
            if ($boundary === null) {
                $segments[] = ['start' => $cursor, 'end' => $until, 'index' => $index];
                break;
            }
            $segments[] = ['start' => $cursor, 'end' => $boundary, 'index' => $index];
            $cursor = $boundary;
            $index = ($index + 1) % 30;
        }

        return $segments;
    }

    /**
     * The instant within ($from, $until] at which the tithi running at
     * $from ends; null when the same tithi still runs at $until.
     */
    public function endOfRunningTithi(DateTimeImmutable $from, DateTimeImmutable $until): ?DateTimeImmutable
    {
        $startIndex = $this->indexAt($from);
        if ($startIndex === $this->indexAt($until)) {
            return null;
        }

        $lo = $from;
        $hi = $until;

        while ($hi->getTimestamp() - $lo->getTimestamp() > 1) {
            $midSeconds = intdiv($lo->getTimestamp() + $hi->getTimestamp(), 2);
            $mid = $lo->setTimestamp($midSeconds);

            if ($this->indexAt($mid) === $startIndex) {
                $lo = $mid;
            } else {
                $hi = $mid;
            }
        }

        return $hi;
    }

    /**
     * The instant at which the tithi running at $at began, searched within
     * ($searchLimit, $at]; null when it already ran at $searchLimit.
     *
     * The tithi index only increases with time, so "index == running index"
     * has exactly one FALSE→TRUE transition in the search window (the
     * running tithi is at most ~27 hours old).
     */
    public function startOfRunningTithi(DateTimeImmutable $at, DateTimeImmutable $searchLimit): ?DateTimeImmutable
    {
        $index = $this->indexAt($at);
        if ($index === $this->indexAt($searchLimit)) {
            return null;
        }

        $lo = $searchLimit;
        $hi = $at;

        while ($hi->getTimestamp() - $lo->getTimestamp() > 1) {
            $midSeconds = intdiv($lo->getTimestamp() + $hi->getTimestamp(), 2);
            $mid = $lo->setTimestamp($midSeconds);

            if ($this->indexAt($mid) === $index) {
                $hi = $mid;
            } else {
                $lo = $mid;
            }
        }

        return $hi;
    }
}
