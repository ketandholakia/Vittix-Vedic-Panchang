# Vittix Panchang — Code Review & Audit Report

> Last generated against the current working tree (uncommitted Milestone-8 work + HEAD).
> Quality gate output verified live: **PHPStan level 8 → `[OK] No errors`**; **PHPUnit → 335 tests, 2050 assertions, 0 failures** (`--exclude-group accuracy`).

## Executive Summary

**Overall Health: Strong.** Vittix Panchang is a mature, pure-PHP 8.2+ Vedic Panchang +
astrology engine with a clean, dependency-inverted architecture, strict types, readonly
value objects, enums, and a comprehensive test suite. The astronomy core has been
substantially refined since the previous review — including a corrected planetary
pipeline (light-time, aberration, of-date frame), a rewritten `TransitionFinder`, and a
rewritten `LunarEventCalculator` with grazing-extremum detection.

| Dimension        | Verdict | Evidence |
|------------------|---------|----------|
| Static analysis  | ✅ Clean | `phpstan analyse src tests tools --level=8` → No errors |
| Tests            | ✅ Green | 335 tests / 2050 assertions, `--exclude-group accuracy` |
| Astronomy core   | ✅ Correct | Meeus Ch. 15/22/25/27/47/49; Swiss-Ephemeris harness in progress |
| Time/DST pipeline| ✅ Correct | `AstroInstant` separates UT/TT; `DateTimeImmutable` + Julian Day |
| Architecture     | ⚠️ Mostly good | `EngineFactory`, PSR-16 cache, per-instance state — plus one service-layer smell |
| Documentation    | ⚠️ Drift | `docs/accuracy.md` is excellent and current; `CODE_REVIEW_REPORT.md` **was stale (see note)** |

### Note: this file was stale and has been regenerated

The previous `CODE_REVIEW_REPORT.md` described a **pre-Milestone-8** codebase and was
actively misleading: it claimed "Only Lahiri supported", "Kshaya Masa not handled",
"Purnimanta not supported", "26 PHPStan errors", and even listed file/class names
(`AdhikaMasaCalculator`, `DeltaT`, `LahiriAyanamsha`) that no longer match `src/`.
Almost all of *its* findings (wiring dedup, ayanamsha options, Kshaya, Purnimanta,
LocationChange tests, Sankranti tests, PHPStan cleanliness, PSR-16 cache) are **already
implemented**. Ironically, the one finding it flagged that is still true — `PanchangService`
wiring duplication — has gotten **worse** since it was written (see CRIT-01). This
regenerated document replaces the stale version.

---

## 1. Bugs / Defects (verified)

### CRIT-01 — `PanchangService::createDefault()` builds a `Panchang` and discards it
- **File:** `src/Panchanga/PanchangService.php` (lines 61–88)
- **Problem:** `createDefault()` calls `Panchang::createDefault(...)` (line 61) and then
  **never uses the result**. It immediately constructs a *second* engine via
  `EngineFactory::createPurePhpEngine(...)` (line 65) and manually re-wires a *subset* of
  the dependency graph (PanchangaCalculator + HinduCalendarCalculator only) into `self`.
- **Impact:**
  1. ~2× wasted engine construction on every instantiation (the discarded `Panchang`
     also built `KundaliCalculator`, `VargaCalculator`, `ShadbalaCalculator`, etc.).
  2. `PanchangService` exposes **none** of the astrology features the `Panchang` facade
     provides — no `kundali()`, `vimshottariDasha()`, `varga()`, `shadbala()`, `yogas()`,
     `festivals()`, `muhurta()`, `electional()`, `ascendant()`, `janmarashi()`. This
     directly contradicts the class docblock ("the canonical API surface for Phase 5
     (REST API)").
  3. The two wiring paths can silently diverge.
- **Fix:** Have `PanchangService` own a `Panchang` instance (obtained from `Panchang::createDefault`)
  and delegate (or expose thin pass-throughs). At minimum, drop the redundant `Panchang`
  construction on line 61.

### CRIT-02 — `PanchangService::getForDate()` clears the in-memory cache after every date
- **File:** `src/Panchanga/PanchangService.php` (lines 148–150)
- **Problem:** After computing one `PanchangDay`, it calls `clearCache()` on the engine.
  `clearCache()` only clears the **in-memory** `positionCache`/`ayanamshaCache` (the fast
  path) and leaves the PSR-16 store untouched. So any batch consumer that reuses one
  `PanchangService` across many dates loses all memoization on the second date onward.
- **Fix:** Remove the unconditional clear; bound memory by cache policy/key rather than
  flushing per call, or make clearing opt-in for long-running batch jobs.

### HIGH-03 — `AdhikaMasaCalculator::findSankranti()` is dead code with a leftover variable
- **File:** `src/Calendar/AdhikaMasaCalculator.php` (lines 53–83)
- **Problem:** `findSankranti()` is defined and public but has **zero callers** anywhere
  in `src/` or `tests/` (verified by grep: only the definition matches). Inside it,
  `$factory = $this->lunarMonth;` (line 63) is assigned and **never used**; it is
  shadowed by `$factory2 = new AstroTimeFactory()` (line 66), which is what the loop
  actually uses. Sankranti times are computed elsewhere (inlined in
  `PanchangService::getAstronomyReport`), so this method is either orphaned API or an
  accidental duplicate.
- **Fix:** Either wire it in (and remove the dead `$factory`), or delete it and the
  unused `AstroTimeFactory` instantiation.

---

## 2. Accuracy issue (documented, decision pending)

### MED-04 — Lahiri ayanamsha constant is ~12″ low vs. the IAE / Swiss standard
- **File:** `src/Astronomy/Sidereal/LahiriAyanamsha.php:36`
  (`LAHIRI_J2000 = 23.853_749_1`); cross-checked in `docs/accuracy.md` (lines 219–224).
- **Measured:** engine −12.04″ at 2000.0, −11.96″ at 2024.0 (effectively constant
  1950–2100). The polynomial has no nutation term, so the *mean*-style ayanamsha is
  subtracted from *apparent* tropical longitudes, adding a ±~17″ Δψ oscillation vs.
  Swiss's true ayanamsha reduction.
- **Impact:** all sidereal outputs (nakshatra, yoga, ascendant, kundali) carry a ~12″
  offset. Tithi/yoga/karana limb math uses **tropical** elongation, so the *Panchang
  limbs* are unaffected — only sidereal astrology outputs.
- **Decision, not a code bug:** per `docs/accuracy.md` (lines 236–239), any constant
  change must be treated as **versioned** (regenerate `tests/fixtures/reference/` and
  rerun `composer accuracy` in the same commit). **Not silently applied.**

### MED-05 — Raman / Krishnamurti ayanamshas are not validated against any reference
- **Files:** `src/Astronomy/Sidereal/RamanAyanamsha.php`, `KrishnamurtiAyanamsha.php`;
  test: `tests/Integration/Astronomy/AyanamshaTest.php`.
- **Problem:** The Swiss-Ephemeris accuracy harness (`docs/accuracy.md` §4, datasets
  table) only covers `SE_SIDM_LAHIRI`. Raman and KP are asserted at a **single epoch**
  (J2000) **against their own formula** (`AyanamshaTest` → Raman 22.4123°, KP 23.7712°).
  A systematic canonical offset would be invisible to the current tests.
- **Fix:** Add Raman (`SE_SIDM_RAMAN`) and KP (`SE_SIDM_KRISHNAMURTI`) to the
  accuracy harness and assert against canonical values.

> I did **not** assert the Raman/KP numeric values are *wrong* here (canonical values
> weren't independently verified). The gap is the missing reference-validation.

---

## 3. Improvements (non-behavioral)

- **Two parallel Purnimanta implementations** (`Astronomy\…/PurnimantaMonthCalculator`
  vs. `Calendar\HinduCalendarCalculator::derivePurnimantaMonth()`). Both encode "+1 month
  in Krishna Paksha," but with different inputs (one reads `tithi->paksha()`, the other
  compares `prevFullMoon` vs `prevNewMoon` JD). Currently consistent, but the logic
  diverges and could disagree exactly at a full-moon boundary. **Dry them.**
- `PurnimantaMonthCalculator` is `new`'d on every `forDay()` call
  (`src/Panchanga/PanchangaCalculator.php:172`) even though it is stateless — **inject once**.
- Stale/unused `use` imports (e.g. `LahiriAyanamsha` in `Panchang.php:16`) — phpstan
  level 8 does not flag unused imports. **Cosmetic cleanup.**

---

## 4. Missing / incomplete features

| Area | State | Location |
|------|-------|----------|
| **Localization wired into output** | Built but **not wired**: `ArrayTranslator::translate()` is never called in `src/`; results only expose English names. | `src/Localization/*.php`; `PanchangDay`/`PanchangResult` |
| **Localization completeness** | `solarmonth` translation missing for 8 of 9 languages (present only in `ml` and `en`). | `src/Localization/{gu,hi,mr,bn,ta,te,kn,sa}/solarmonth.php` absent |
| **Shadbala** | Partial: `drikBala` hardcoded `0.0` (`ShadbalaCalculator.php:65`); `kalaBala` is an Ayana-Bala proxy ("true Kala Bala requires Paksha, Vara, Hora, etc." `ShadbalaCalculator.php:59`). ChestaBala is a stub (all direct motion → 15 virupas; ignores speed). | `src/Astrology/ShadbalaCalculator.php`, `…/Shadbala/*` |
| **Dignity** | Only Exalted/Debilitated/Moolatrikona/OwnSign; Friendly/Neutral/Enemy return `null` ("to be added"). | `src/Astrology/DignityCalculator.php:20` |
| **Yogas** | 6 of the classical set only: Gajakesari + 5 Pancha Mahapurusha. The ~20–30 common yogas (Chandra-Mangal, Budha-Aditya, Dhana, Raja yogas, …) are absent. | `src/Astrology/YogaDetector.php:28-35` |
| **Eclipses** | Not present in `src/` (Graha/Surya Grahana is a standard Panchang feature). A reference impl lives only in the untracked `scratch/` workspace. | (absent from `src/`) |
| **True Rahu/Ketu** | Mean nodes only (documented, acceptable for Vedic). | `Enum/Body.php` |
| **Dasha** | Vimshottari only (no other amsa); returns Mahadasha + Antardasha only (no Pratyantardasha). | `src/Astrology/VimshottariDashaCalculator.php` |

---

## 5. Test-coverage notes

- 80 test files. Coverage is broad: every limb calculator, varga, dasha, dignity,
  shadbala (+ all 5 sub-calculators), yogas, muhurta, electional, festivals, samvat,
  localization, plus `BoundaryTransitionTest`, `RandomizedInvariantTest`,
  `AmantaVsPurnimantaTest`, `KshayaMasaTest`, `AdhikaMasaBoundaryTest`,
  `SankrantiMomentTest`, `ElevationParallaxTest`, both `LocationChangeTest` variants.
- **Swiss-Ephemeris accuracy harness is uncommitted in-progress work**: `tests/Accuracy/`,
  `tools/`, `tests/fixtures/reference/`, `docs/accuracy-report.md`, and
  `src/Panchanga/TransitionSearchException.php` are all untracked. It requires Python +
  `pyswisseph` (`pip install -r tools/reference/requirements-dev.txt`) and is **excluded**
  from the default `composer test` group — it self-skips when fixtures are absent, so the
  335-green run does **not** yet prove Swiss-parity; that assertion lives in the accuracy
  group.

---

## 6. Packaging / repository hygiene

- `.gitignore` is well-formed (ignores `vendor/`, caches, and root debug scripts). The
  `debug_*.php` / `check_*.php` / `find_*.php` / `test_*.php` / `update_oracle.php` /
  `random_test_results.php` in the repo root are **properly ignored, not committed** — the
  prior "stray debug scripts" concern is resolved.
- **Stray committed file:** `ascendant.patch` at repo root is tracked but referenced
  nowhere — remove it or gitignore it.
- `scripts/` (6 dev/utility scripts) is committed and reasonable. `scratch/` is a large
  local reference workspace but only **9 files** are tracked (the hundreds of Go/Python/
  Besselian-element files are untracked) — not a shipped-package problem.
- Duplicate-looking `tests/Integration/LocationChangeTest.php` and
  `tests/Integration/Panchanga/LocationChangeTest.php` test **different facades**
  (`Panchang` vs `PanchangService`) and are both green — acceptable, but consolidating
  the shared assertions would reduce maintenance.

---

## 7. Prioritized change list

| Priority | Task | Effort | Evidence / Location |
|----------|------|--------|---------------------|
| P0 | Make `PanchangService` wrap a `Panchang` (delegate, don't re-wire/discard) | 2–4 h | `PanchangService.php:61-88` |
| P0 | Expose astrology features on `PanchangService` (kundli, dasha, varga, shadbala, yogas, ascendant, festivals, muhurta, electional) | 2 h | `Panchang.php` facade vs `PanchangService.php` |
| P1 | Stop clearing the in-memory cache after every date in `PanchangService` (batch perf) | 1 h | `PanchangService.php:148` |
| P1 | Decide + version the Lahiri 12″ realignment (if canonical parity is required) | 1 d | `LahiriAyanamsha.php:36`; `docs/accuracy.md:219-230` |
| P1 | Wire `ArrayTranslator` into output + finish `solarmonth` for 8 languages | 3 h | `src/Localization/`, `ArrayTranslator.php` |
| P1 | Add Raman/KP ayanamshas to the Swiss-Ephemeris accuracy harness | 1 d | `docs/accuracy.md` §4; `AyanamshaTest.php` |
| P2 | Remove or wire `findSankranti`; delete dead `$factory` | 1 h | `AdhikaMasaCalculator.php:53-66` |
| P2 | Consolidate the two Purnimanta implementations; inject `PurnimantaMonthCalculator` | 4 h | `PurnimantaMonthCalculator.php`, `HinduCalendarCalculator.php:108`, `PanchangaCalculator.php:172` |
| P3 | Complete Shadbala (Drik Bala; full Kala Bala; real Chesta Bala speed model) | 2 d | `ShadbalaCalculator.php:57-65`, `ChestaBalaCalculator.php` |
| P3 | Complete Dignity (Friendly/Neutral/Enemy) | 4 h | `DignityCalculator.php:20` |
| P3 | Expand Yoga set beyond Pancha-Mahapurusha + Gajakesari | 3 d | `YogaDetector.php:28-35` |
| P3 | Add eclipse calculation (Graha/Surya Grahana) | 2 d | (absent from `src/`) |
| P3 | Commit the accuracy harness or document it as optional; clean `ascendant.patch` | 1 h | working tree (untracked `tests/Accuracy/`, `tools/`); root `ascendant.patch` |

---

## 8. What was verified correct (not flagged)

To avoid false positives, the following core math was read and confirmed correct and/or
covered by the passing accuracy suite:

- `TransitionFinder` (coarse 0.2-d cap + secant/bisection, 0.5 s tolerance, throws on
  non-convergence) — robust; the ≤0.2-d step cannot skip a karana (shortest ~0.41 d).
- `LunarEventCalculator` — civil-date selection, parabola-checked grazing recovery,
  h₀ = 0.7275·π − 34′, ±1.5-day windowing.
- `SolarEventCalculator` — Meeus Ch. 15 transit/iteration, h₀ = −0.8333° + elevation dip.
- `SolarPositionCalculator`, `LunarPositionCalculator` — VSOP87/ELP2000-82, nutation,
  annual aberration; `PlanetPositionCalculator` light-time uses the **TT** path only once
  (avoids the double-ΔT regression noted in commit `5a795ca`).
- `SiderealTimeCalculator` (Meeus 12.4), `CoordinateTransformer`, `AscendantCalculator`,
  `VimshottariDashaCalculator` (120-year sequence, balance-of-lord start), `Angle`,
  `JulianDay` (Gregorian↔JD incl. 1582 reform), `AstroTimeFactory` (UT→ΔT→TT).
- Tithi/Nakshatra/Yoga/Karana/Vara limb math and the sunrise-anchored day boundary.
- `PurnimantaMonthCalculator` and the Panchang limb transition logic, including the
  "second tithi on the same day" capture in `PanchangaCalculator::forDay`.

---

*Generated by live verification (PHPStan + PHPUnit + source inspection) against the
working tree at `main` (with uncommitted Milestone-8 changes).*
