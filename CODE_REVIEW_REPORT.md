# Vittix Panchang — Code Review & Audit Report

> Regenerated 2026-11-15 against `main` after the November defect-fix round and the
> follow-up wiring round (commits `11c3e70`…`21b194f`).
> Quality gate output verified live: **PHPStan level 8 → `[OK] No errors`**; **PHPUnit →
> 406 tests / 4115 assertions, 0 failures** (default suite, ~2.4 min after the slow-group
> split; `composer test:slow` runs the 67 remaining property/oracle/boundary tests).

## Executive Summary

**Overall Health: Strong.** Vittix Panchang is a mature, pure-PHP 8.2+ Vedic Panchang +
astrology engine with a clean, dependency-inverted architecture, strict types, readonly
value objects, enums, and a comprehensive test suite.

The November 2026 defect round fixed seven verified behavioral defects that earlier
reviews had missed — most importantly a **180°-inverted Ascendant**, **Purnimanta months
wrong around Adhika months**, a **festival engine with Adhika double-fires and
wrong-day results**, and **calendar-month Samvat shortcuts**. All are now covered by
regression tests.

| Dimension        | Verdict | Evidence |
|------------------|---------|----------|
| Static analysis  | ✅ Clean | `phpstan analyse src tests tools --level=8` → No errors |
| Tests            | ✅ Green | 398 tests / 4052 assertions, `--exclude-group accuracy` |
| Astronomy core   | ✅ Correct | Meeus Ch. 15/22/25/27/47/49; Swiss-Ephemeris harness in `tests/Accuracy/` |
| Time/DST pipeline| ✅ Correct | `AstroInstant` separates UT/TT; `DateTimeImmutable` + Julian Day |
| Architecture     | ✅ Good | `EngineFactory`, namespaced PSR-16 cache keys, single Purnimanta implementation |
| Documentation    | ✅ Current | `docs/accuracy.md`, `docs/api.md`, `docs/USAGE.md`, `docs/astrology.md` updated with the fixes |

---

## 1. Bugs / Defects — November 2026 round (all fixed, all regression-tested)

### FIXED-1 (was CRITICAL) — Ascendant was 180° off (returned the Descendant)
- **File:** `src/Astronomy/Coordinates/AscendantCalculator.php` (`tropicalLongitude`).
- **Problem:** both tan(λ) numerator and denominator carried the wrong sign, so every
  Ascendant, Kundali house assignment and any yoga/Varga lagna derived from it was
  antipodal. Earlier review rounds listed `AscendantCalculator` under "verified correct".
- **Fix:** Meeus eq. 32.1 sign convention (`y = cos(RAMC)`, `x = −(sin ε·tan φ + cos ε·sin RAMC)`).
- **Regression tests:** sunrise ≈ Sun longitude (~208° Tula on 2026-11-15), sunset =
  Sun+180°, equator/RAMC=0 → 90° tropical (`AscendantCalculatorTest`).

### FIXED-2 — PSR-16 cache keys collided across ayanamsha systems and used `:`
- **File:** `src/Astronomy/Engine/PurePhpAstronomyEngine.php`.
- **Problem:** keys like `pos:sun:<jd>` used a PSR-16 reserved character (strict caches
  such as Symfony's throw), and the ayanamsha key had no system component — a Lahiri and
  a Raman engine sharing one cache returned each other's values.
- **Fix:** `vpanchang.pos.<body>.<jd>` / `vpanchang.ayan.<system>.<jd>`;
  `AyanamshaCalculatorInterface::id()`.
- **Regression tests:** `PurePhpAstronomyEngineCacheKeyTest` (distinct shared-cache
  values for Lahiri/Raman/KP; no `{}()/\@:` in any key).

### FIXED-3 — Purnimanta month wrong around Adhika months
- **Files:** `PurnimantaMonthCalculator`, `HinduCalendarCalculator`, `PanchangaCalculator::forDay`.
- **Problem:** Krishna Paksha was blindly shifted +1 from the Amanta month name. In 2023
  (Adhika Shravana) Aug 5/10 were reported as Bhadrapada instead of nija Shravana.
- **Fix:** Purnimanta = full-moon-to-full-moon, named after the Amanta lunation that
  begins with the first new moon after its starting full moon; Adhika status follows that
  naming lunation (`LunarMonthCalculator::amantaLunation()` single-sources lunation
  classification). The old two-implementation split (tithi-based vs phase-order) is gone.
- **Regression tests:** `PurnimantaAdhikaTest` (Jul 25 / Aug 5 / Aug 10 / Jul 10 2023;
  kshaya 1963-12-15), `PurnimantaAgreementPropertyTest` (200 random instants).

### FIXED-4 — Samvat years used calendar-month shortcuts
- **File:** `src/Calendar/SamvatCalculator.php`.
- **Problem:** Vikram flipped on April 1, Shaka on March 22, Gujarati on November 1.
- **Fix:** exact variants (`vikramSamvatExact`, `shakaSamvatExact`, `gujaratiSamvatExact`)
  resolve Chaitra / Kartika Shukla Pratipada through the lunar machinery + tithi at
  sunrise; the date-only methods remain as `@deprecated` approximations.
- **Regression tests:** `SamvatCalculatorExactTest` (2025-03-29 → 2081 / 2025-03-31 →
  2082; Gujarati 2025-10-21 → 2081 / 2025-10-25 → 2082).

### FIXED-5 — Festival engine: Adhika double-fires, missed festivals, wrong-day results
- **Files:** `FestivalEngine`, `FestivalCatalog`, `Rules/SunriseTithiRule`, `Rules/ViddhaTithiRule`.
- **Problems:** 2023 Raksha Bandhan/Janmashtami fired twice (Adhika month not skipped);
  2026 Gudi Padwa and Janmashtami were never reported (single-instant sampling missed
  razor-edge tithi windows); Diwali was one day late in 2023/2025/2026 (Amavasya at
  sunrise instead of at Pradosh); Akshaya Tritiya 2026 was one day late.
- **Fix:** rules scored by exact tithi/kala-window overlap (bisection on elongation, real
  next sunrise), Adhika months skipped, one date per year with a documented nearest-window
  fallback; Pradosh for Diwali, Madhyahna for Ganesha Chaturthi/Rama Navami/Akshaya
  Tritiya, Nishita attributed to the civil day whose sunset begins the night.
- **Regression tests:** `FestivalResolutionTest` (every catalog festival resolves to
  exactly one date 2023–2026; drik-verified dates), plus updated
  `FestivalEngineTest`/`ViddhaTithiRuleTest`/`MajorFestivalsTest`.

### FIXED-6 — Vimshottari balance-dasha Antardashas were scaled to the remaining time
- **File:** `src/Astrology/VimshottariDashaCalculator.php`.
- **Fix:** full-length Antardasha timeline from the Mahadasha's true start (birth −
  elapsed), drop pre-birth parts, truncate the running one; full proportions thereafter.
- **Regression tests:** `VimshottariDashaCalculatorTest` (1990-05-05 birth: Rahu running
  at birth; full-length Sun-Jupiter; exact remaining-time sum).

### FIXED-7 — Choghadiya Tuesday/Wednesday night rows were wrong
- **File:** `src/Muhurta/MuhurtaCalculator.php`.
- **Fix:** all 14 rows derived structurally from the single cycle (day: weekday-lord
  offset +1/segment; night: (dayStart+5) mod 7, −2/segment).
- **Regression tests:** `MuhurtaCalculatorChoghadiyaTest` (all 14 rows derived
  independently).

## 2. Previously flagged — status

| Finding | Status |
|---------|--------|
| CRIT-01 `PanchangService::createDefault()` discarded a constructed `Panchang` | ✅ Fixed — `fromPanchang()` shares one object graph; astrology pass-throughs exposed |
| CRIT-02 `getForDate()` cleared the engine cache after every date | ✅ Fixed — per-result LRU only; `clearCache()` is opt-in |
| HIGH-03 dead `AdhikaMasaCalculator::findSankranti()` | ✅ Removed (no longer exists in `src/`) |
| MED-04 Lahiri ayanamsha constant ~12″ vs IAE/Swiss | ⏳ Decision pending — versioned change + fixture regeneration required (see `docs/accuracy.md`) |
| MED-05 Raman/KP ayanamshas lack reference validation | ⏳ Open — add `SE_SIDM_RAMAN`/`SE_SIDM_KRISHNAMURTI` to the accuracy harness |
| Two parallel Purnimanta implementations | ✅ Fixed — single implementation in `Calendar\PurnimantaMonthCalculator` |
| `PurnimantaMonthCalculator` re-created per `forDay()` call | ✅ Fixed — constructed from the injected `LunarMonthCalculator` |
| Localization not wired into output | ✅ Wired — `PanchangService::translate()` and `getForDateLocalized()` call `ArrayTranslator` (verified by reading `PanchangService` and the localization test suite) |
| Exact Samvat not wired into `calendar()` (new, Nov round 2) | ✅ Fixed — `Panchang::calendar()`/`PanchangService::calendar()` take `?GeoLocation`; `samvatIsApproximate` flags the fallback; `PanchangResult.gujaratiSamvat` added (`SamvatWiringTest`) |
| Samvat new-year day disagreed with the festival engine (new) | ✅ Fixed — shared `Festivals\NewYearResolver`; year flips at 00:00 local on the festival-resolved day (`SamvatNewYearAlignmentTest`, 2023–2027) |
| Mean-obliquity polynomial ran 10× too fast (new, caught by the new Ascendant oracle) | ✅ Fixed — Laskar polynomial now evaluated at u = millennia/10; matches IAU 2006/Swiss to ~0.01″ (`AscendantOracleTest`, 200 samples ±60° lat) |
| Dead `$eventsToday` in `FestivalEngine::resolveYear()` (new) | ✅ Removed |
| Vimshottari oracle | ⏳ Open — pyjhora unavailable in the dev environment; no trusted reference, nothing fabricated |

---

## 3. Remaining improvement candidates (non-behavioral)

- **Shadbala completion** — Drik Bala is a 0 placeholder and Kala Bala is an Ayana-Bala
  proxy; both are now *programmatically flagged* via
  `ShadbalaValues::isPartial('drik'|'kala')` so consumers cannot mistake them for full
  classical values. Chesta Bala remains a simplified speed model.
- **Dignity** — only Exalted/Debilitated/Moolatrikona/OwnSign; Friendly/Neutral/Enemy
  return `null` (`DignityCalculator`).
- **Yogas** — 6 of the classical set (Gajakesari + 5 Pancha Mahapurusha).
- **Eclipses** — not present in `src/` (a reference implementation lives in `scratch/`).
- **True Rahu/Ketu** — mean nodes only (documented, acceptable for Vedic use).
- **Dasha** — Vimshottari only; Mahadasha + Antardasha (no Pratyantardasha).
- **Localization completeness** — `solarmonth` tables exist for `en`/`ml` only.

## 4. Test-coverage notes

- 90+ test files. Every limb calculator, varga, dasha, dignity, shadbala (incl. sub-
  calculators), yogas, muhurta, electional, festivals, samvat, localization, plus
  boundary/property/oracle suites. Slow suites are tagged `#[Group('slow')]` and run by
  `composer test:slow` (separate CI job); the default `composer test` suite is ~2.4 min.
- The Swiss-Ephemeris accuracy harness (`tests/Accuracy/`, `tools/`,
  `tests/fixtures/reference/`) is **excluded** from the default `composer test` group
  (`composer accuracy` runs it); it self-skips when fixtures are absent. Default-green
  does not by itself prove Swiss parity — that lives in the accuracy group.
- Note: Vimshottari oracle coverage is still missing (pyjhora unavailable; no trusted
  reference was fabricated).

## 5. What was verified correct — and HOW

Each entry records the verification method: **oracle** (independent reference data),
**property test** (exhaustive/randomized invariant), or **code reading** (manual review
against the cited algorithm — the weakest method; the Ascendant, Vimshottari and
obliquity bugs survived exactly this way before oracles existed).

- `AscendantCalculator` + MC — **oracle**: 200 Swiss-Ephemeris samples (±60° lat,
  1950–2100) within 0.05° (`AscendantOracleTest`); found and fixed the obliquity bug.
- Mean obliquity — **oracle**: matches IAU 2006 / Swiss to ~0.01″ across 1950–2100.
- `LunarMonthCalculator` phase searches and per-instance memoization — **code reading**
  + **property test** (`PurnimantaAgreementPropertyTest`, 200 random instants);
  `amantaLunation()` classification — **property test** + drik-anchored boundary dates
  (`PurnimantaAdhikaTest`).
- `PurnimantaMonthCalculator` full-moon-to-full-moon naming — **property test** +
  drik-anchored dates (`PurnimantaAdhikaTest`).
- Festival engine — **oracle**: 40 drikpanchang dates (8 catalog festivals × 2023–2027,
  each asserted for Ahmedabad and Delhi), 37/40 exact, 3 documented convention
  divergences (Akshaya Tritiya 2027, Diwali 2024, Krishna Janmashtami 2027 —
  `FestivalOracleTest` computes and prints the counts per city). The tiered
  Raksha Bandhan rule brought all five Raksha dates (2023–2027) into exact
  agreement.
- Samvat years — **code reading** + boundary tests against drik-verified Yugadi/Diwali
  dates (`SamvatCalculatorExactTest`, `SamvatNewYearAlignmentTest`, `SamvatWiringTest`).
- `TithiTimeline` bisection primitives — **code reading** (single-transition guarantees)
  + indirectly by the festival oracle.
- `SolarEventCalculator` (h₀ = −0.8333°, dip 1.92′·√elevation) — **oracle**
  (`SunMoonEventAccuracy`, 10 cities × 60 dates, two conventions).
- Limb transitions (tithi/nakshatra/yoga/karana ends) — **oracle**
  (`TransitionAccuracy`, sub-second root-found records).
- Body longitudes (Sun/Moon/planets/mean node, tropical+sidereal) — **oracle**
  (`LongitudeAccuracy`, 2000 instants vs Swiss).
- `PurePhpAstronomyEngine` PSR-16 cache keys — **unit test**
  (`PurePhpAstronomyEngineCacheKeyTest`).
- Vimshottari Antardashas — **unit test** against a hand-computed full-length timeline
  (`VimshottariDashaCalculatorTest`); no independent reference (see open item).

### Unreviewed areas (no oracle, no thorough manual review this round)

- **Planetary VSOP87 term tables** (`src/Astronomy/Planetary/*Terms.php`) — covered only
  in aggregate by the body-longitude oracle (term errors could cancel).
- **Nutation series** (`NutationCalculator`) — only indirectly via obliquity/ascendant.
- **Ayanamsha constants** (Lahiri/Raman/KP polynomials) — Lahiri vs Swiss is within the
  oracle tolerance; Raman/KP still lack reference validation (MED-05).
- **Lunar rise/set** (`LunarEventCalculator` grazing/parabola recovery) — an oracle
  exists (`sun_moon_events`) but the grazing-extremum recovery paths are not
  independently verified.

---

*Generated by live verification (PHPStan + PHPUnit + Swiss/drikpanchang oracles +
source inspection) against `main`.*
