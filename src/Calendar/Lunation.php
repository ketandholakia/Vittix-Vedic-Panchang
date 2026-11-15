<?php

declare(strict_types=1);

namespace Vittix\Panchang\Calendar;

use Vittix\Panchang\Enum\LunarMonth;

/**
 * A single lunation (one new moon to the next) and the lunar month name
 * it carries under the Amanta convention.
 *
 *   • Normal lunation: exactly one Sankranti → named after the sign the
 *     Sun enters during it.
 *   • Adhika (intercalary): no Sankranti → named after the following
 *     lunation, flagged adhika.
 *   • Kshaya (decayed): two Sankrantis → one month name is skipped;
 *     flagged kshaya.
 */
final readonly class Lunation
{
    public function __construct(
        public readonly LunarMonth $month,
        public readonly bool $isAdhika,
        public readonly bool $isKshaya,
    ) {
    }
}
