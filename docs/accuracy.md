# Accuracy and Supported Date Range

Vittix Panchang uses a highly optimized pure-PHP astronomical engine designed for standard astrological and calendrical computations.

## Supported Date Range

**The officially supported and tested date range is 1900–2100 AD.**

Calculations outside this range will still execute without throwing errors, but the precision degrades exponentially.

### Why 1900–2100?

1. **Delta T Approximations**: The time difference between Terrestrial Time (TT) and Universal Time (UT) is known as Delta T (ΔT). This engine uses the standard polynomial approximations from Espenak and Meeus for the 20th and 21st centuries. For dates outside this range (especially historical dates before 1600 AD), these polynomials diverge from historical eclipse records.

2. **Orbital Perturbations**: A high-precision engine (like VSOP87 or JPL DE431) evaluates thousands of periodic terms for planetary orbits. For extreme speed in pure PHP, this engine evaluates a truncated series of periodic terms (usually those > 0.0001 degrees) and assumes linear/quadratic changes to orbital elements. These truncated terms are highly accurate near J2000.0 but drift over millennia.

3. **Nutation & Precession**: The IAU nutation models and precession matrices used here are linearized around J2000.0.

## Expected Precision (1900–2100)

Within the supported range, the engine provides the following precision compared to JPL DE-based Swiss Ephemeris:

| Calculation | Typical Error | Max Error | Implication for Panchang |
|-------------|---------------|-----------|--------------------------|
| Sun Longitude | < 0.0003° (1") | 0.0008° | Negligible (~2 seconds drift in Sankranti) |
| Moon Longitude | < 0.002° (7") | 0.005° (18") | Minor (~10 seconds drift in Tithi boundaries) |
| Lunar Node (Rahu/Ketu) | < 0.001° | 0.002° | Negligible |
| Planets (Mercury-Saturn) | < 0.005° | 0.02° | Negligible for house placements, slight drift in exact aspect timings |
| Ayanamsha (Lahiri/Raman/KP) | Exact | Exact | Matches standard polynomial definitions precisely |
| Ascendant (Lagna) | < 0.01° | 0.02° | < 30 seconds in Lagna degree |
| Sunrise/Sunset | < 10 seconds | 30 seconds | Includes refraction & dip correction |

### Near-Boundary Warnings

Panchang limbs (Tithi, Nakshatra, etc.) are discrete periods. Because there is a very small computational variance (e.g. ±10 seconds for a Tithi end time), if a limb ends exactly at sunrise (within a few seconds), the engine might flip the *active* limb for that day.

The engine detects this automatically and sets the `accuracy->nearBoundary` flag to `true` when a transition happens within **2 minutes of sunrise**, warning you that the calculated result is sensitive to minute computational differences or location coordinate precision.

### Horizon Dip and Elevation

Sunrise and Sunset calculations incorporate standard atmospheric refraction and horizon dip based on observer elevation.

- Standard altitude `h₀ = -0.8333°` (refraction 34' + solar semi-diameter 16')
- Dip correction `= 1.92 × √(elevation in meters)` arcminutes

This ensures accuracy for mountainous locations where sunrise occurs earlier than at sea level.

## AccuracyInfo Object

Every `PanchangDay` and `PanchangResult` includes an `AccuracyInfo` object:

```php
$day->accuracy->nearBoundary;                    // bool
$day->accuracy->estimatedAngularErrorDegrees;    // float
$day->accuracy->estimatedTimeErrorSeconds;       // float
```

| Property | Description |
|----------|-------------|
| `nearBoundary` | `true` if any limb transition within 2 minutes of sunrise |
| `estimatedAngularErrorDegrees` | Estimated max positional error in degrees |
| `estimatedTimeErrorSeconds` | Estimated max timing error for limb boundaries |

## Architecture & Accuracy Features

- **Planetary positions**: Truncated VSOP87 series (Sun, Mercury-Saturn), ELP2000-82 (Moon)
- **Nutation**: IAU 1980 63-term series
- **Obliquity**: Mean + true obliquity with nutation
- **Sidereal time**: IAU 2000/2006 polynomials (J2000.0)
- **Ayanamsha**: Configurable (Lahiri, Raman, Krishnamurti) with true ayanamsha = mean + Δψ
- **Ascendant**: Meeus formulas with nutation/obliquity
- **Lunar nodes**: Mean nodes from Ω term (standard for Vedic)
- **Root finding**: Bracketed secant refinement with forced-bisection safeguard, fractional-JD precision,
  0.5-second tolerance; throws `TransitionSearchException` on non-convergence (never returns an
  unrefined bound)
- **Time scale**: TT = UT + ΔT (Espenak/Meeus polynomials)

## Comparison with Other Engines

| Feature | Vittix Panchang | Swiss Ephemeris (sweph) | JPL DE431 |
|---------|-----------------|-------------------------|-----------|
| Language | Pure PHP 8.2+ | C library + PHP ext | C/Fortran |
| Extensions | None required | `sweph` PHP extension | Native |
| Speed | ~5-10ms (warm) | ~1-5ms | ~10-50ms |
| Precision (1900-2100) | < 0.01° | < 0.001° | < 0.0001° |
| Date range | 1900-2100 (official) | 3000 BC - 3000 AD | 1550-2650 AD |
| Dependencies | PSR-16 cache (opt) | Swiss Ephemeris files | JPL kernels |

## When to Use What

**Use Vittix Panchang when:**
- Building PHP/Laravel applications
- Need pure PHP with no extensions
- Date range 1900-2100
- Sub-arcminute accuracy sufficient
- Want zero-config deployment

**Consider Swiss Ephemeris when:**
- Need arcsecond precision
- Historical dates before 1600 AD
- Already have C extension capability
- Building high-precision professional software

## Verification

The test suite includes regression snapshots against known reference data. Run tests to verify accuracy:

```bash
composer test
```

Key test categories:
- Planetary position regression tests
- Panchang limb boundary tests
- Sunrise/sunset accuracy tests
- Ayanamsha verification tests
- Festival date verification tests

## Validation (Swiss Ephemeris harness)

The tables above are estimates. This repository also ships a measurement
harness that compares the engine against a trusted reference — **Swiss
Ephemeris** (through `pyswisseph`) — and reports the real, measured errors.
Where measured results differ from the estimates above, trust the measured
report ([docs/accuracy-report.md](accuracy-report.md)).

### What is compared

| Dataset | Reference (Swiss Ephemeris) | Engine output | Units |
|---|---|---|---|
| 2000 random instants, 1950–2100 | apparent tropical longitude, Lahiri sidereal longitude, ayanamsha for Sun, Moon, Mercury–Saturn, mean Rahu | `tropicalLongitude()`, `siderealLongitude()`, `ayanamsa()` | arcsec, shortest-angle signed difference |
| 300 start instants × 4 limbs | next end of current tithi / nakshatra / yoga / karana, found by sub-second root finding on Swiss longitudes | `TransitionFinder::findTithiEnd()` etc. | seconds |
| 10 cities × 60 dates (2000–2050) | `swe.rise_trans` in two conventions: (i) upper limb + standard refraction, (ii) disc centre, no refraction | `solarEvents()`, `lunarEvents()` | seconds |

Reference details: pyswisseph 2.10.03, **Moshier ephemeris** by default (no
data files needed; `--ephemeris swiss` switches to the Swiss Ephemeris
`.se1` files if they are installed), ayanamsha `SE_SIDM_LAHIRI`, and a fixed
seed (20260929) so runs are reproducible.

### Regenerate the reference fixtures

```bash
pip install -r tools/reference/requirements-dev.txt
python tools/reference/generate_fixtures.py            # full set, Moshier
python tools/reference/generate_fixtures.py --quick    # tiny set for pipeline checks
python tools/reference/generate_fixtures.py --ephemeris swiss --ephe-path /path/to/ephe
```

Fixtures are written to `tests/fixtures/reference/*.json` with a metadata
header (swisseph version, ephemeris, ayanamsha, seed, generation date).
The accuracy tests skip cleanly when the fixtures are absent.

### Run the tests

```bash
composer accuracy     # the accuracy group only (tagged #[Group('accuracy')])
composer test         # default suite; excludes the accuracy group for speed
```

Tolerances live in `tests/Accuracy/Tolerances.php`: each asserted constant is
calibrated to the current measured worst case (documented in its comment),
with the tighter goal kept next to it as `*_TARGET_*`. Tighten the constants
as the engine improves.

### Run the report

```bash
composer accuracy:report             # writes docs/accuracy-report.md and prints it
composer accuracy:report -- --json   # machine-readable summary on stdout
php tools/accuracy_report.php --no-write   # print only, do not update the file
```

The report is stamped with the generation date and the current git commit.

### How to read the report

- **Longitudes** — errors in arcseconds (magnitudes; signed values only in
  the worst-case lists). `sidereal ≈ tropical − ayanamsha`, so a constant
  ayanamsha offset appears in both columns.
- **Transitions** — errors in seconds. Comparisons above
  `Tolerances::TRANSITION_ANOMALY_SECONDS` are counted as anomalies and
  classified via the pointing/residual decomposition: *pointing* = engine's
  own boundary vs the reference (longitude error), *residual* = engine's
  returned time vs its own boundary (refinement/solver effect).
- **Events** — errors in seconds for events matched within ±2 minutes;
  `missed` = engine returned null where the reference has an event;
  `off-window` = engine time is closer to a neighbouring day's event
  (selection difference).
- Each section ends with the worst cases **including their inputs** (UTC
  instants / cities / dates) so they can be reproduced by hand.

The harness only measures: it never modifies `src/`. Bugs and convention
mismatches it finds are reported in its output rather than silently patched.

### Lahiri ayanamsha: comparison against Swiss Ephemeris and the IAE (report only)

The engine's `LahiriAyanamsha` is a cubic polynomial
(23.8537491° + 1.396971°T + 0.000308°T² + 0.000002°T³ at J2000.0, T in
Julian centuries). This note compares it with the Swiss Ephemeris
`SIDM_LAHIRI` implementation and with the value published by the
**Indian Astronomical Ephemeris (IAE)**. The engine constant was
**deliberately not changed** in this review.

| Epoch (12h UT) | Vittix (this engine) | Swiss `SIDM_LAHIRI` mean | Swiss `SIDM_LAHIRI` true | IAE published value |
|---|---|---|---|---|
| 2000-01-01 | 23.853749° = 23°51′13.50″ | 23.857092° = 23°51′25.53″ | 23.853222° = 23°51′11.60″ | ≈ 23°51′25.5″ |
| 2024-01-01 | 24.189040° = 24°11′20.54″ | 24.192363° = 24°11′32.51″ | 24.190871° = 24°11′27.13″ | — |

- **Mean vs true.** The Swiss *mean* value is the geometric ayanamsha;
  the *true* value adds the nutation in longitude (Δψ) and is what
  `SEFLG_SIDEREAL` actually subtracts from apparent tropical longitudes.
  The IAE value commonly quoted for 2000.0 (23°51′25.5″; standard
  interpolation 23°51′25.5″ + 50.29″·(Y−2000)) corresponds to the Swiss
  **mean** ayanamsha (agreement ≈ 0.03″). Indian national ephemerides refer
  their sidereal (nirayana) positions to the *true* equinox, i.e. they
  reduce with the true ayanamsha — the same convention Swiss Ephemeris
  implements.
- **Our engine.** The polynomial has no nutation term, so it behaves like a
  *mean-style* ayanamsha, but its J2000.0 constant sits **≈ 12″ below** the
  IAE/Swiss mean value (measured: −12.04″ at 2000.0, −11.96″ at 2024.0, and
  effectively constant over 1950–2100). This offset propagates into every
  sidereal longitude; relative to Swiss sidereal output there is an
  additional Δψ-sized oscillation of up to ±17″ because our sidereal
  reduction does not remove nutation the way Swiss does.
- **Recommendation.** Follow the IAE/Swiss convention: (1) align the
  polynomial constant with the IAE mean value (or adopt the official IAE
  series/precession rate); and (2) treat the returned ayanamsha as the
  *mean* ayanamsha and subtract it together with Δψ from apparent tropical
  longitudes when producing sidereal output — matching how `SEFLG_SIDEREAL`
  and the Indian ephemeris reduce nirayana coordinates.

The measured effect of the current offset is visible in the sidereal
columns of the generated report ([docs/accuracy-report.md](accuracy-report.md)),
which absorb the constant offset plus the nutation term.

> **If the constant is ever changed, treat it as a versioned change:** it
> shifts every sidereal output, so regenerate the fixtures
> (`python tools/reference/generate_fixtures.py`) in the same commit and
> re-run `composer accuracy` / `composer accuracy:report`.

### Known comparator differences (engine vs Swiss Ephemeris)

Two differences between the engine and the reference are *policy/convention*
choices, not ephemeris errors, and they explain the largest late-century or
high-latitude residuals:

1. **ΔT model for future dates.** The engine uses Espenak–Meeus polynomials;
   Swiss Ephemeris uses a flatter prediction. They agree within a second near
   1950–2020 but diverge afterwards: engine ΔT is ≈ +18 s higher by 2050 and
   ≈ +110 s higher by 2100. Fast bodies inherit this as a position offset
   (Moon ≈ 60″, Mercury/Venus ≈ 20–30″ at 2100 — e.g. the Moon's 74″ maximum
   is dominated by it). With ΔT matched (Swiss fed the engine's TT), the
   series and the pipeline agree within ≈1″. Compare with ΔT matched when the
   question is “is the ephemeris right”, and as-shipped when the question is
   “do the two implementations agree”.
2. **Horizon conventions at extreme grazing latitudes.** Near the polar edge
   (e.g. Svalbard) moonrise/moonset can be a graze of minutes; the engine's
   geocentric h₀ = 0.7275·π − 34′ and Swiss `rise_trans` (topocentric) can
   then disagree on whether a brief event exists at all, and matching events
   can differ by minutes (rate → 0 amplifies any model difference). This only
   affects razor-edge cases; for all test cities the conventions agree to
   ≤ 48 s (see §3.5 of the report).