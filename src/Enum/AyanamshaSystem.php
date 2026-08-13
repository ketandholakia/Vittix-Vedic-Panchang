<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * Supported Ayanamsha (precession) systems.
 */
enum AyanamshaSystem: string
{
    /** Lahiri / Chitrapaksha Ayanamsha (Standard Indian Govt) */
    case Lahiri = 'lahiri';

    /** Raman Ayanamsha (B.V. Raman) */
    case Raman = 'raman';

    /** Krishnamurti (KP) Ayanamsha */
    case Krishnamurti = 'krishnamurti';
}
