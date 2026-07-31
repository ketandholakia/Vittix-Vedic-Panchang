<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

use DateTimeImmutable;

final readonly class TimeWindow
{
    public function __construct(
        public string $name,
        public DateTimeImmutable $start,
        public DateTimeImmutable $end,
    ) {
    }

    public function durationSeconds(): int
    {
        return $this->end->getTimestamp() - $this->start->getTimestamp();
    }
}
