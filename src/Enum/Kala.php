<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

use Vittix\Panchang\Festivals\FestivalDayContext;

use Vittix\Panchang\ValueObject\TimeWindow;

/**
 * The Karmakala (ritual time window) a festival tithi must prevail in.
 *
 * All windows belong to one civil date D (the date that "owns" them):
 *
 *   • Sunrise:   the whole Panchang day [sunrise(D), sunrise(D+1)] — the
 *                classical "tithi prevailing at sunrise" convention, with
 *                the greater-overlap fallback for tithis that touch no
 *                sunrise.
 *   • Madhyahna: 3rd of the 5 equal segments of [sunrise(D), sunset(D)].
 *   • Aparahna:  4th of the 5 equal segments of [sunrise(D), sunset(D)].
 *   • Pradosh:   the first two night-muhurtas after sunset(D)
 *                ([sunset, sunset + 2·night/15]).
 *   • Nishita:   the 8th of the 15 night-muhurtas of the night belonging
 *                to D ([sunset(D) + 7·night/15, sunset(D) + 8·night/15]).
 *                The night is attributed to the civil day whose sunset
 *                begins it, so Nishita windows normally straddle midnight
 *                (e.g. Janmashtami Sep 3 with its puja past midnight).
 */
enum Kala: string
{
    case Sunrise = 'sunrise';
    case Madhyahna = 'madhyahna';
    case Aparahna = 'aparahna';
    case Pradosh = 'pradosh';
    case Nishita = 'nishita';

    /**
     * The kala window owned by the given day, or null when the required
     * solar events do not exist (polar day/night).
     */
    public function window(FestivalDayContext $context): ?TimeWindow
    {
        return match ($this) {
            self::Sunrise => $this->sunriseDay($context),
            self::Madhyahna => $this->daySegment($context, 2),
            self::Aparahna => $this->daySegment($context, 3),
            self::Pradosh => $this->pradosh($context),
            self::Nishita => $this->nishita($context),
        };
    }

    private function sunriseDay(FestivalDayContext $context): ?TimeWindow
    {
        if ($context->sunrise === null || $context->nextSunrise === null) {
            return null;
        }

        return new TimeWindow('Sunrise', $context->sunrise, $context->nextSunrise);
    }

    private function daySegment(FestivalDayContext $context, int $index): ?TimeWindow
    {
        if ($context->sunrise === null || $context->sunset === null) {
            return null;
        }

        return $this->segment($context->sunrise, $context->sunset, 5, $index, $this->value);
    }

    private function pradosh(FestivalDayContext $context): ?TimeWindow
    {
        if ($context->sunset === null || $context->nextSunrise === null) {
            return null;
        }

        // Pradosh = the first two muhurtas of the night (night = 15 muhurtas).
        $night = $context->nextSunrise->getTimestamp() - $context->sunset->getTimestamp();
        $end = $context->sunset->setTimestamp($context->sunset->getTimestamp() + (int) round(2.0 * $night / 15.0));

        return new TimeWindow($this->value, $context->sunset, $end);
    }

    private function nishita(FestivalDayContext $context): ?TimeWindow
    {
        if ($context->sunset === null || $context->nextSunrise === null) {
            return null;
        }

        // Nishita = the 8th of the 15 night-muhurtas of the night that
        // begins at this day's sunset.
        $night = $context->nextSunrise->getTimestamp() - $context->sunset->getTimestamp();
        $start = $context->sunset->setTimestamp($context->sunset->getTimestamp() + (int) round(7.0 * $night / 15.0));
        $end = $context->sunset->setTimestamp($context->sunset->getTimestamp() + (int) round(8.0 * $night / 15.0));

        return new TimeWindow($this->value, $start, $end);
    }

    private function segment(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        int $count,
        int $index,
        string $name,
    ): TimeWindow {
        $length = ($to->getTimestamp() - $from->getTimestamp()) / $count;
        $start = $from->setTimestamp($from->getTimestamp() + (int) round($index * $length));
        $end = $from->setTimestamp($from->getTimestamp() + (int) round(($index + 1) * $length));

        return new TimeWindow($name, $start, $end);
    }
}
