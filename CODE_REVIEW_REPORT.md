# Vittix Panchang — Code Review & Audit Report

> Regenerated 2026-11-15 against `main` after the November defect-fix round
> (commits `11c3e70`…`26dcea3`).
> Quality gate output verified live: **PHPStan level 8 → `[OK] No errors`**; **PHPUnit →
> 398 tests, 4052 assertions, 0 failures** (`--exclude-group accuracy`).

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
| Localization not wired into output | ✅ Wired — `PanchangService::translate()` and localized output use `ArrayTranslator` |

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
  boundary/property/oracle suites (`BoundaryTransitionTest`, `RandomizedInvariantTest`,
  `PurnimantaAgreementPropertyTest`, `FestivalResolutionTest`, `SankrantiMomentTest`,
  `ElevationParallaxTest`, both `LocationChangeTest` variants).
- The Swiss-Ephemeris accuracy harness (`tests/Accuracy/`, `tools/`,
  `tests/fixtures/reference/`) is **excluded** from the default `composer test` group
  (`composer accuracy` runs it); it self-skips when fixtures are absent. Default-green
  does not by itself prove Swiss parity — that lives in the accuracy group.
- Note: the default suite currently takes ~6 minutes; if it keeps growing, split the
  property/oracle suites into a dedicated group or raise `COMPOSER_PROCESS_TIMEOUT`
  (composer's default 300 s process timeout aborts `composer test`).

## 5. What was verified correct (this round)

- `LunarMonthCalculator` phase searches (0.5° scan + bisection, 10 s tolerance) and the
  new per-instance memoization; `amantaLunation()` classification (normal/Adhika/Kshaya).
- `PurnimantaMonthCalculator` full-moon-to-full-moon naming rule (property-tested over
  200 random instants 1950–2100).
- `TithiTimeline` bisection primitives (`endOfRunningTithi`, `startOfRunningTithi`) —
  single-transition guarantees documented and used by the festival overlap scorer.
- `SolarEventCalculator` (Meeus Ch. 15; h₀ = −0.8333°, dip 1.92′·√elevation — docblock
  now matches the code).
- `PurePhpAstronomyEngine` position/ayanamsha caching incl. the namespaced PSR-16 keys.
- `SamvatCalculator` lunar new-year walk (bounded 16-lunation back-scan, sunrise-anchored
  year change).

---

*Generated by live verification (PHPStan + PHPUnit + source inspection + drikpanchang
cross-checks for festival dates) against `main`.*
