# Vittix Panchang — Comprehensive Code Review Report

## Executive Summary

**Overall Health**: **Excellent** — A well-architected, mathematically sound, and thoroughly tested Panchang engine implemented in pure PHP 8.2+.

| Dimension | Score | Notes |
|-----------|-------|-------|
| Architecture | 9/10 | Clean separation of concerns, dependency inversion, composable services |
| Panchang Accuracy | 9/10 | Validated against independent oracle (Swiss Ephemeris + manual verification) |
| Date/Time Handling | 9/10 | Robust timezone-aware pipeline using DateTimeImmutable + Julian Day |
| Location Handling | 9/10 | GeoLocation value object, no hard-coded coordinates in core engine |
| API Integration | N/A | No external API dependency — pure calculation engine |
| Testing | 9/10 | 292 tests: unit, integration, oracle, boundary, randomized invariants |
| Performance | 8/10 | In-request memoization; no persistent cache; acceptable for batch use |
| Code Quality | 8/10 | Strict types, readonly classes, enums; minor PHPStan issues |
| Maintainability | 9/10 | Small focused classes, clear interfaces, comprehensive test coverage |

---

## Architecture Assessment

### Architecture Pattern
```
User / API Layer
       ↓
PanchangService (Application Facade)
       ↓
Panchang (Domain Facade)
       ↓
PanchangaCalculator (Orchestrator)
    ├── TithiCalculator
    ├── NakshatraCalculator
    ├── YogaCalculator
    ├── KaranaCalculator
    ├── VaraCalculator
    ├── TransitionFinder
    ├── LunarMonthCalculator
    └── AdhikaMasaCalculator
       ↓
AstronomyEngineInterface (PurePhpAstronomyEngine)
    ├── SolarPositionCalculator
    ├── LunarPositionCalculator
    ├── SolarEventCalculator
    ├── LunarEventCalculator
    ├── SiderealTimeCalculator
    ├── LahiriAyanamsha
    ├── NutationCalculator
    └── ObliquityCalculator
```

### Strengths
1. **Clean dependency inversion** — `AstronomyEngineInterface` allows engine swapping (testing, future precision upgrades)
2. **Single responsibility** — Each calculator handles exactly one Panchang limb
3. **Composition root** — `Panchang::createDefault()` / `PanchangService::createDefault()` wire everything
4. **Value objects** — `GeoLocation`, `AstroInstant`, `PanchangDay`, `PanchangResult` are `readonly` with validation
5. **No global state** — Engine caches are per-instance and request-scoped

### Concerns
1. **PanchangService::createDefault() duplicates wiring** from `Panchang::create()` (lines 55-82). Should delegate to `Panchang::createDefault()` and extract dependencies via getters, or use a shared factory.
2. **`LunarEventCalculator` takes `AstronomyEngineInterface`** creating a circular dependency risk (engine → lunar events → engine). Currently resolved by lazy construction in `PurePhpAstronomyEngine` constructor (line 96).

---

## Panchang Calculation Assessment

### Tithi (Lunar Day) — ✅ Correct
- **Formula**: `elongation = normalize(moon_tropical - sun_tropical)`, `index = floor(elongation / 12°)`
- **Reference**: Standard tropical elongation, no ayanamsha needed
- **Implementation**: `TithiCalculator::calculate()` and `index()` — correct
- **Boundary handling**: `TransitionFinder` coarse scan (30-min) + bisection (5-sec tolerance) — robust

### Nakshatra — ✅ Correct
- **Formula**: `sidereal_moon_longitude`, `index = floor(lon / (360/27))`
- **Pada**: `floor(lon / (360/108)) % 4 + 1`
- **Implementation**: `NakshatraCalculator` uses `siderealLongitude(Body::Moon)` — correct
- **Ayanamsha**: Applied via engine's `siderealLongitude()` → `tropical - ayanamsha`

### Yoga — ✅ Correct
- **Formula**: `normalize(sidereal_sun + sidereal_moon)`, `index = floor(sum / (360/27))`
- **Implementation**: `YogaCalculator` — correct

### Karana — ✅ Correct
- **Formula**: Half-tithi index `floor(elongation / 6°)`, mapped via fixed/repeating scheme
- **Implementation**: `KaranaCalculator::halfTithiIndex()` + `KaranaEnum::fromHalfTithiIndex()` — correct
- **Fixed karanas**: Kimstughna (0), Shakuni (57), Chatushpada (58), Naga (59) — matches tradition

### Vara (Weekday) — ✅ Correct
- **Rule**: Sunrise-to-sunrise, not midnight-to-midnight
- **Implementation**: `VaraCalculator` checks if moment < sunrise → yesterday's weekday — correct

### Sunrise/Sunset — ✅ Correct (Meeus Ch. 15)
- **Algorithm**: Iterative refinement from estimated transit
- **Altitude**: h₀ = -0.8333° (refraction 34' + semi-diameter 16')
- **Elevation correction**: Dip = 1.76' × √elevation_m
- **Tolerance**: 0.5 minutes, max 50 iterations — adequate
- **Polar handling**: Returns null for midnight sun / polar night

### Moonrise/Moonset — ✅ Correct (Meeus Ch. 15)
- **Topocentric**: Uses horizontal parallax (π) and refraction
- **Apparent altitude**: h₀ = 0.7275 × π - 34'
- **Daily rate**: 347.8° (accounts for Moon's ~13.2°/day motion)
- **Validation**: Events outside ±1.5 days filtered out

### Lunar Month (Amanta) — ✅ Correct
- **Algorithm**: Previous/next new moon → check if Sun transits solar sign
- **Month name**: Sign entered during lunation → Chaitra=Mesha, etc.
- **Adhika Masa**: No solar sign transition between consecutive new moons — correct

### Adhika Masa — ✅ Correct
- **Detection**: `siderealSolarSign(prevNewMoon) === siderealSolarSign(nextNewMoon)`
- **Kshaya Masa**: Not handled (documented limitation)

### Ayanamsha (Lahiri) — ✅ Correct
- **Formula**: Polynomial from J2000.0: 23.853749° + 1.396971°/cy + 0.000308°/cy² + 0.000002°/cy³
- **Calibration**: Matches Lahiri's revised 1985 value at J2000.0 (~23°51'30")

### Planetary Positions
- **Sun**: VSOP87 truncated (Meeus Ch. 25) — accuracy < 0.01° for 1900–2100
- **Moon**: ELP2000/82 truncated (60 terms) — accuracy ≤ 10″ longitude, ≤ 30″ latitude
- **Planets**: Truncated VSOP87 series (Mercury, Venus, Mars, Jupiter, Saturn)
- **Nutation**: IAU 1980 theory (Meeus Ch. 22)
- **Obliquity**: Mean + nutation correction (Meeus Ch. 22)

---

## Location & Timezone Assessment

### GeoLocation Value Object — ✅ Excellent
```php
final readonly class GeoLocation {
    public function __construct(
        public readonly float $latitude,   // -90 to +90, North positive
        public readonly float $longitude,  // -180 to +180, East positive
        public readonly float $elevationMeters = 0.0
    ) { /* validation */ }
}
```
- **No hard-coded coordinates** anywhere in calculation engine
- **TestLocations** only in test support code
- **Longitude sign**: Positive = East (standard), matches Meeus

### Timezone Pipeline — ✅ Correct
```
Local DateTimeImmutable (with timezone)
    → AstroTimeFactory::fromDateTime()
        → setTimezone('UTC')
        → JulianDay::fromUtc() → JD_UT
        → DeltaT::forYear() → ΔT seconds
        → JD_TT = JD_UT + ΔT/86400
    → AstroInstant (carries UTC DateTime, JD_UT, JD_TT, ΔT)
```
- **All astronomical calculations use `AstroInstant` (JD_TT)**
- **Solar/lunar events returned in original timezone** via `JulianDay::toUtc($jd)->setTimezone($timezone)`
- **No device/server timezone leakage** — timezone always from input `DateTimeImmutable`

### Date Boundary Handling — ✅ Correct
- **Panchang day = sunrise to next sunrise** (not civil midnight)
- `PanchangaCalculator::forDay()` uses `solarEvents->sunrise` as anchor
- Falls back to local noon if no sunrise (polar regions)
- `VaraCalculator` correctly handles pre-sunrise moments

---

## Caching Assessment

### Current State
- **In-memory memoization only** in `PurePhpAstronomyEngine`:
  - `positionCache`: keyed by `Body|JD_TT` (8 decimal places)
  - `ayanamshaCache`: keyed by `JD_TT` (6 decimal places)
- **Not persisted** — cleared per engine instance
- **`clearCache()` method** available for batch processing

### Cache Key Analysis — ✅ Adequate
Keys include:
- Body (Sun, Moon, planets, Rahu, Ketu)
- JD_TT (includes ΔT, so timezone-aware)
- **Missing**: Location (not needed for geocentric positions)

### Risk
- Engine instance reuse across requests with different locations **is safe** because geocentric positions don't depend on location
- Topocentric calculations (sunrise, moonrise) are **not cached** — they call `solarEvents()`/`lunarEvents()` which compute fresh each time

---

## API Assessment

**No external API dependency** — this is a self-contained calculation library.
- `PanchangService` is the public API facade for JSON/REST consumers
- Input validation via `GeoLocation` constructor and PHP's `DateTimeImmutable`
- Output via `PanchangResult::toJson()` / `toArray()`

---

## Security Assessment

| Aspect | Status |
|--------|--------|
| API Keys / Secrets | N/A — no external services |
| HTTPS / Certificates | N/A |
| Input Validation | ✅ `GeoLocation` validates lat/lon ranges |
| SQL Injection | N/A — no database |
| XSS / Injection | N/A — library only, JSON output is structured |
| Logging Sensitive Data | None observed |

---

## Performance Assessment

| Operation | Complexity | Notes |
|-----------|------------|-------|
| Single PanchangDay | ~50-100 ms | Dominated by astronomical series evaluation |
| Batch (365 days) | ~5-10 s | Acceptable with `clearCache()` between dates |
| Memory | < 5 MB | No persistent caches |

**Optimizations available**:
1. Precompute `DeltaT` for year ranges
2. Cache solar events per (date, location) if batching same location
3. Consider Brent's method for faster transition convergence

---

## Test Coverage Assessment

### Test Categories
| Category | Count | Coverage |
|----------|-------|----------|
| Unit (calculators, math, enums) | ~120 | All limb calculators + boundary logic |
| Integration (Panchanga) | ~40 | End-to-end with real engine |
| Oracle (Independent) | 8 dates | Swiss Ephemeris + manual verification |
| Fixture Regression | 8 dates | Snapshots from engine (exact match) |
| Boundary Transitions | 3 | Tithi at ±30s from sunrise |
| Edge Cases | 6 | Polar, DST, ancient, future |
| Randomized Invariants | 1 (50 iterations) | Sunrise<sunset, valid ranges |
| Lunar Oracle | 5 locations | USNO moonrise validation |

### Golden Reference Testing — ✅ Strong
- **Independent Oracle**: 8 dates × 3 continents vs. Swiss Ephemeris (swetest) + manual verification
- **Tolerance**: 120 seconds for event times (appropriate for algorithmic precision)
- **Locations**: Ahmedabad, New Delhi, Ayodhya, London, New York, Sydney, Tokyo

### Missing Test Coverage
1. **Adhika Masa boundary dates** — only one test (2023 Shravana)
2. **Kshaya Masa** — not implemented, not tested
3. **Sankranti exact moment** — no dedicated test
4. **Multi-location same-date comparison** — no regression test proving location change changes results
5. **Purnimanta month system** — only Amanta tested

---

## Critical Findings

### CRIT-01: PanchangService Wiring Duplication
| | |
|---|---|
| **File** | `src/Panchanga/PanchangService.php:55-82` |
| **Problem** | `createDefault()` duplicates the entire dependency wiring from `Panchang::create()` |
| **Root Cause** | Copy-paste instead of delegation |
| **Impact** | Maintenance burden; divergence risk if `Panchang::create()` changes |
| **Fix** | Have `PanchangService::createDefault()` call `Panchang::createDefault()` and extract dependencies via getters, or introduce a shared `EngineFactory` |
| **Regression Test** | Verify both facades produce identical results for same inputs |

### CRIT-02: Missing Multi-Location Regression Test
| | |
|---|---|
| **File** | (Missing test) |
| **Problem** | No test verifies that changing `GeoLocation` changes Panchang results |
| **Root Cause** | Oversight in test matrix |
| **Impact** | Silent location bugs would not be caught |
| **Fix** | Add test: same date, Ahmedabad vs New Delhi → verify sunrise, tithi end times differ |
| **Test Case** | `Date = 2026-08-05, Loc A = Ahmedabad (23.02, 72.57), Loc B = Delhi (28.61, 77.21)` — assert sunrise differs by ~20 min |

---

## High-Priority Findings

### HIGH-01: Purnimanta Month System Not Supported
| | |
|---|---|
| **File** | `src/Calendar/LunarMonthCalculator.php` |
| **Problem** | Only Amanta (new moon to new moon) implemented; Purnimanta (full moon to full moon) used in North India |
| **Impact** | Incorrect lunar month names for users following Purnimanta tradition |
| **Fix** | Add `PurnimantaMonthCalculator` or mode parameter; expose via `PanchangService` |

### HIGH-02: Kshaya Masa (Lost Month) Not Handled
| | |
|---|---|
| **File** | `src/Calendar/AdhikaMasaCalculator.php:19-20` |
| **Problem** | Documented as not handled; occurs ~once per 19 years |
| **Impact** | Rare but significant calendar error when it occurs |
| **Fix** | Implement Kshaya Masa detection (two Sankrantis in one lunation) |

### HIGH-03: DeltaT Polynomial Accuracy Beyond 2050
| | |
|---|---|
| **File** | `src/Time/DeltaT.php:221-230` |
| **Problem** | PolynomialBeyond2150 uses quadratic extrapolation; uncertainty grows rapidly |
| **Impact** | Sunrise/moon times drift by minutes per century beyond 2150 |
| **Fix** | Document supported range (1900–2100 recommended); consider IERS bulletin integration for future dates |

### HIGH-04: Elevation Not Used in Solar Parallax
| | |
|---|---|
| **File** | `src/Astronomy/Solar/SolarEventCalculator.php:162` |
| **Problem** | Elevation used for refraction dip only, not for solar parallax correction |
| **Impact** | Minor (< 1 sec at 5000m) but technically incomplete |
| **Fix** | Add parallax correction: `parallax = 8.794″ / distance_AU × cos(alt)` |

---

## Medium-Priority Findings

### MED-01: LunarEventCalculator Circular Dependency
| | |
|---|---|
| **File** | `src/Astronomy/Engine/PurePhpAstronomyEngine.php:96` |
| **Problem** | `LunarEventCalculator` takes `AstronomyEngineInterface` (the engine itself) |
| **Impact** | Architecture smell; lazy construction workaround needed |
| **Fix** | Extract `LunarPositionProvider` interface with only `equatorialPosition(Body::Moon)` and `position(Body::Moon)` |

### MED-02: No Persistent Cache for Batch Operations
| | |
|---|---|
| **File** | `src/Astronomy/Engine/PurePhpAstronomyEngine.php` |
| **Problem** | In-memory cache lost between requests; batch calendar generation recomputes everything |
| **Impact** | Generating 1-year calendar takes ~10s |
| **Fix** | Add optional `CacheInterface` (PSR-16) for position/ayanamsha; key by `(body, JD_TT)` |

### MED-03: Missing Ayanamsha Options
| | |
|---|---|
| **File** | `src/Astronomy/Sidereal/LahiriAyanamsha.php` |
| **Problem** | Only Lahiri supported; Raman, Krishnamurti, Yukteshwar used by other traditions |
| **Impact** | Limits adoption for users of other ayanamshas |
| **Fix** | Add `RamanAyanamsha`, `KrishnamurtiAyanamsha` implementing `AyanamshaCalculatorInterface` |

### MED-04: PHPStan Errors (26)
| | |
|---|---|
| **Files** | Multiple (see `composer analyse` output) |
| **Issues** | Match arm always true, unhandled match values, iterable value types, test nullability |
| **Impact** | Static analysis fails at level 8; some may indicate real bugs |
| **Fix** | Address each error; most are type annotation improvements |

---

## Low-Priority Findings

### LOW-01: Magic Numbers in SolarEventCalculator
| | |
|---|---|
| **File** | `src/Astronomy/Solar/SolarEventCalculator.php:38,41,44` |
| **Values** | `H0 = -0.8333`, `TOLERANCE_MINUTES = 0.5`, `MAX_ITER = 50` |
| **Fix** | Add doc comments explaining source (Meeus) and make `const` with descriptive names |

### LOW-02: TestLocations Only Has Ahmedabad
| | |
|---|---|
| **File** | `tests/Support/TestLocations.php` |
| **Fix** | Add major cities: Delhi, Mumbai, Kolkata, Chennai, Varanasi, Ujjain, Haridwar |

### LOW-03: Documentation Gaps
| | |
|---|---|
| **Files** | `docs/accuracy.md`, `docs/panchang.md` |
| **Issue** | Very brief; no algorithm references, precision claims, or limitation disclosures |
| **Fix** | Expand with Meeus chapter references, accuracy tables, supported date ranges |

---

## Regression Test Plan

### Required New Tests

#### 1. Location Change Regression Test (P0)
```php
// tests/Integration/LocationChangeTest.php
public function testLocationChangeAffectsPanchang(): void
{
    $date = new DateTimeImmutable('2026-08-05', new DateTimeZone('Asia/Kolkata'));
    
    $ahmedabad = new GeoLocation(23.0225, 72.5714);
    $delhi = new GeoLocation(28.6139, 77.2090);
    
    $panchangA = $this->service->getForDate($date, $ahmedabad);
    $panchangB = $this->service->getForDate($date, $delhi);
    
    // Sunrise must differ (Delhi is ~0.5° further east, ~2 min earlier sunrise)
    $this->assertNotEquals(
        $panchangA->solarEvents->sunrise?->getTimestamp(),
        $panchangB->solarEvents->sunrise?->getTimestamp(),
        'Sunrise must differ by location'
    );
    
    // Tithi end times must differ (different sunrise anchor)
    $this->assertNotEquals(
        $panchangA->tithiEndsAt?->getTimestamp(),
        $panchangB->tithiEndsAt?->getTimestamp(),
        'Tithi end must differ by location'
    );
}
```

#### 2. Adhika Masa Boundary Tests (P1)
- Test 2023 Shravana Adhika Masa (known)
- Test 2042 Adhika Masa (future)
- Test transition dates: day before/after Adhika Masa starts

#### 3. Sankranti Exact Moment Tests (P1)
- Verify solar sign transition time matches Meeus within 60 seconds
- Test Mesha Sankranti (Apr 14/15), Makara Sankranti (Jan 14/15)

#### 4. Purnimanta vs Amanta Comparison (P2)
- Same date, both systems, verify month names differ correctly

#### 5. Multi-Location Oracle Matrix (P2)
| Date | Ahmedabad | Delhi | Mumbai | Kolkata | Chennai | London | New York |
|------|-----------|-------|--------|---------|---------|--------|----------|
| 2026-01-14 (Makar) | ✅ | ✅ | | | | | |
| 2026-08-15 (Indep) | | ✅ | | | | | |
| 2024-10-31 (Diwali) | ✅ | | | | | | |

---

## Refactoring Plan

### Phase 0 — Protect Current Behavior (Week 1)
- [ ] Add LocationChangeTest (P0)
- [ ] Add AdhikaMasaBoundaryTest (P1)
- [ ] Add SankrantiMomentTest (P1)
- [ ] Capture current API output as golden snapshots
- [ ] Fix PHPStan errors (26 issues)

### Phase 1 — Correctness Enhancements (Week 2-3)
- [ ] Implement PurnimantaMonthCalculator
- [ ] Implement KshayaMasa detection
- [ ] Add Raman/Krishnamurti ayanamsha options
- [ ] Add solar parallax correction for elevation
- [ ] Document supported date range (1900–2100 recommended)

### Phase 2 — Architecture Improvements (Week 3-4)
- [ ] Extract `EngineFactory` to eliminate wiring duplication
- [ ] Break `LunarEventCalculator` circular dependency via `LunarPositionProvider`
- [ ] Add optional PSR-16 cache for batch operations
- [ ] Make `DeltaT` source configurable (polynomial vs IERS table)

### Phase 3 — Testing Expansion (Week 4-5)
- [ ] Complete multi-location oracle matrix (10+ locations)
- [ ] Add Purnimanta vs Amanta comparison tests
- [ ] Add property-based tests for limb boundaries (QuickCheck style)
- [ ] Add CI matrix: PHP 8.2, 8.3, 8.4

### Phase 4 — Performance (Week 5+)
- [ ] Profile batch generation; add persistent cache
- [ ] Optimize transition finder (Brent's method)
- [ ] Consider precomputed planetary position tables for common date ranges

---

## Risk Matrix

| Risk | Severity | Probability | Impact | Priority |
|------|----------|-------------|--------|----------|
| Location mismatch (cached data reuse) | Critical | Low | Incorrect Panchang for wrong city | P0 |
| Timezone confusion (local vs UTC) | Critical | Low | ±1 day error in Panchang | P0 |
| Adhika Masa miscalculation | Critical | Medium | Wrong lunar month for ~1 month/2.7 yr | P0 |
| Kshaya Masa not detected | High | Very Low | Lost month ~once/19 yr | P1 |
| Purnimanta users get wrong month | High | Medium | Wrong month name for North India users | P1 |
| DeltaT drift beyond 2050 | Medium | High | Sunrise error grows ~1 min/decade | P2 |
| Ayanamsha mismatch for tradition | Medium | Medium | All sidereal limbs offset | P2 |
| Circular dependency breaks DI | Low | Low | Architecture rigidity | P3 |
| No persistent cache for batch | Low | High | Slow calendar generation | P3 |

---

## Final Verdict

**Vittix Panchang is a production-ready, mathematically sound, and well-tested library.**

### Strengths
- Pure PHP, no extensions, no network dependencies
- Clean architecture with proper separation of concerns
- Comprehensive test suite including independent oracle validation
- Correct handling of sunrise-based day boundaries, timezone pipeline, location independence
- Transparent algorithms with Meeus references

### Recommended Immediate Actions
1. **Add LocationChangeTest** — critical regression guard
2. **Fix PHPStan errors** — maintain code quality gate
3. **Document Purnimanta limitation** — set user expectations
4. **Extract EngineFactory** — eliminate wiring duplication

### Recommended Short-Term Enhancements
1. Implement Purnimanta month system
2. Add Kshaya Masa detection
3. Support additional ayanamshas (Raman, Krishnamurti)
4. Add optional persistent caching for batch operations

---

**REVIEW COMPLETE**

### Prioritized Change List

| Priority | Task | Effort | Risk if Deferred |
|----------|------|--------|------------------|
| P0 | Add LocationChangeTest | 1 hr | Silent location bugs |
| P0 | Fix 26 PHPStan errors | 4 hrs | CI quality gate failure |
| P1 | Document Purnimanta limitation | 1 hr | User confusion |
| P1 | Extract EngineFactory | 2 hrs | Wiring divergence |
| P1 | Implement PurnimantaMonthCalculator | 1 day | North India adoption blocked |
| P2 | Add Kshaya Masa detection | 2 days | Rare but real calendar error |
| P2 | Add Raman/Krishnamurti ayanamsha | 1 day | Tradition coverage |
| P3 | Add PSR-16 cache option | 1 day | Batch performance |
| P3 | Break LunarEventCalculator circular dep | 4 hrs | Architecture cleanup |
