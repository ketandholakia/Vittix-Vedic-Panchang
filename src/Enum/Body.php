<?php

declare(strict_types=1);

namespace Vittix\Panchang\Enum;

/**
 * Celestial bodies supported by the astronomy engine.
 *
 * Additional bodies (Mercury, Venus, Mars, Jupiter, Saturn) can be added
 * here without changing the AstronomyEngineInterface.
 */
enum Body: string
{
    case Sun  = 'sun';
    case Moon = 'moon';
    case Mercury = 'mercury';
    case Venus = 'venus';
    case Mars = 'mars';
    case Jupiter = 'jupiter';
    case Saturn = 'saturn';

    /**
     * Mean ascending lunar node (Rahu in Vedic astrology).
     *
     * This is the *mean* node — derived directly from the Ω term already
     * used internally for nutation (see LunarArgumentCalculator) — not the
     * true (perturbed) node. Mean node accuracy is standard practice for
     * Vedic astrology and is within a degree of the true node at all times.
     */
    case RahuMean = 'rahu_mean';

    /**
     * Mean descending lunar node (Ketu in Vedic astrology).
     *
     * Always exactly 180° from RahuMean — the two are never computed
     * independently.
     */
    case KetuMean = 'ketu_mean';
}
