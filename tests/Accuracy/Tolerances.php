<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Accuracy;

/**
 * Central, configurable tolerances for the accuracy test group.
 *
 * Philosophy (see docs/accuracy.md):
 *   • The *_MAX_* constants are the assertions the suite enforces today.
 *     They are calibrated to the current measured worst case (fixture set of
 *     2026-09-29, Swiss Ephemeris 2.10.03 / Moshier) with a deliberate
 *     headroom of ≥ ≈1.5× (counts get a small absolute slack) so the tests
 *     do not flake on float/platform or toolchain variation.
 *   • The *_TARGET_* values are documented goals, not asserted yet.
 *
 * Recalibrating after regenerating fixtures or fixing the engine:
 *   composer accuracy:report   (see docs/accuracy.md § Validation)
 * then bump the constants to measured max × ≈1.5.
 *
 * Units: arcseconds for longitudes, seconds for times.
 */
final class Tolerances
{
    // ---------------------------------------------------------------------
    // Longitudes (arcseconds) — measured max |error| on 2000 samples
    // ---------------------------------------------------------------------

    /**
     * Asserted: max |error| of apparent tropical longitudes, per body.
     * Measured: sun 34.3, moon 74.2, rahu_mean 18.8, planets ≤ 9.1 (after the
     * VSOP87D precession fix and the light-time ΔT fix; was ~5100).
     * Target: sun 5, moon 15, planets 5.
     *
     * @var array<string, float>
     */
    public const LONGITUDE_TROPICAL_MAX_ARCSEC = [
        'sun' => 50.0,          // measured 34.3
        'moon' => 110.0,        // measured 74.2
        'mercury' => 20.0,      // measured 9.1   (was 5113.1)
        'venus' => 12.0,        // measured 6.4   (was 5108.2)
        'mars' => 8.0,          // measured 3.8   (was 5103.1)
        'jupiter' => 6.0,       // measured 2.5   (was 5095.7)
        'saturn' => 6.0,        // measured 1.4   (was 5093.2)
        'rahu_mean' => 28.0,    // measured 18.8
        'default' => 110.0,
    ];

    /**
     * Asserted: max |error| of Lahiri sidereal longitudes, per body.
     * Measured: sun 62.0, moon 91.8, rahu_mean 12.1, planets ≤ 36.9
     * (planet sidereal values include the ≈12″ mean-ayanamsha offset and the
     * Δψ term; see the Lahiri note in docs/accuracy.md).
     * Target: sun 15, moon 25, planets 20.
     *
     * @var array<string, float>
     */
    public const LONGITUDE_SIDEREAL_MAX_ARCSEC = [
        'sun' => 95.0,          // measured 62.0
        'moon' => 140.0,        // measured 91.8
        'mercury' => 55.0,      // measured 36.9
        'venus' => 55.0,        // measured 34.7
        'mars' => 50.0,         // measured 33.4
        'jupiter' => 50.0,      // measured 31.5
        'saturn' => 50.0,       // measured 31.5
        'rahu_mean' => 18.0,    // measured 12.1
        'default' => 140.0,
    ];

    /** Asserted: max |error| vs Swiss mean (geometric) Lahiri ayanamsha. Measured 12.19″. Target 2″. */
    public const AYANAMSHA_MEAN_MAX_ARCSEC = 18.0;

    /** Asserted: max |error| vs Swiss true (nutation-included) Lahiri ayanamsha. Measured 30.66″. Target 18″. */
    public const AYANAMSHA_TRUE_MAX_ARCSEC = 45.0;

    // ---------------------------------------------------------------------
    // Transitions (seconds) — measured on 300 start instants × 4 limbs
    // ---------------------------------------------------------------------

    /**
     * A transition error beyond this many seconds is treated as an anomaly
     * (solver artifact / boundary race) rather than a pointing error;
     * such cases are counted and reported separately.
     * Measured pointing-only tail: ≤ 206 s (yoga), so the cutoff sits above it.
     */
    public const TRANSITION_ANOMALY_SECONDS = 250.0;

    /** Asserted: max |error| of transition times after excluding anomalies. Measured 206.0 s (yoga). Target 60 s. */
    public const TRANSITION_MAX_SECONDS = 300.0;

    /**
     * Asserted: max number of anomalous comparisons per limb.
     * Was tithi 3, yoga 2, karana 4 before the TransitionFinder fix;
     * measured 0 after it. Keep at zero.
     */
    public const TRANSITION_MAX_ANOMALIES = 0;

    // ---------------------------------------------------------------------
    // Sun / Moon events (seconds) — measured on 600 city-dates
    // ---------------------------------------------------------------------

    /**
     * Which fixture convention the tests assert against. The report compares
     * both; the engine matches the upper-limb + standard-refraction
     * convention (as documented in its own interface).
     */
    public const SUN_EVENT_CONVENTION = 'upper_limb_refraction';
    public const MOON_EVENT_CONVENTION = 'upper_limb_refraction';

    /** Asserted: max |error| of sunrise/sunset vs upper-limb refraction. Measured 48.8 s. Target 30 s. */
    public const SUN_EVENT_MAX_SECONDS = 75.0;

    /** Asserted: max |error| of same-day-matched moonrise/moonset. Measured 48.2 s. Target 60 s. */
    public const MOON_EVENT_MAX_SECONDS = 75.0;

    /** Asserted: max count of sun events where the engine returned null. Measured 0. */
    public const SUN_EVENT_MAX_MISSED = 0;

    /** Asserted: max count of sun events with no reference candidate within the match window. Measured 0. */
    public const SUN_EVENT_MAX_UNMATCHED = 0;

    /**
     * Asserted: max count of moon events where the engine returned null but the
     * reference has an event on that local date. Was 344 before the
     * LunarEventCalculator local-date fix; measured 0 after it (engine nulls
     * match the reference's skip days exactly). Slack of 2 covers razor-edge
     * local-midnight cases.
     */
    public const MOON_EVENT_MAX_MISSED = 2;

    /**
     * Asserted: max count of moon events the engine placed outside the ±2h
     * match window (a neighbouring day's event). Was 424 before the
     * local-date fix; measured 0 after it.
     */
    public const MOON_EVENT_MAX_SELECTION_MISMATCH = 2;

    /**
     * Asserted: max count of moon events that matched a neighbouring day's
     * event *within* the match window (flagged "moved"). Measured 0.
     */
    public const MOON_EVENT_MAX_MOVED = 2;
}
