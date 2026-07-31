<?php

declare(strict_types=1);

namespace Vittix\Panchang\Festivals;

final readonly class Festival
{
    public function __construct(
        public string $name,
        public FestivalRuleInterface $rule
    ) {
    }
}
