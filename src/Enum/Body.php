<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * Celestial bodies supported by the astronomy engine.
 *
 * Additional bodies (Mercury, Venus, Mars, Jupiter, Saturn, Rahu/Ketu nodes)
 * can be added here without changing the AstronomyEngineInterface.
 */
enum Body: string
{
    case Sun  = 'sun';
    case Moon = 'moon';
}
