# Vittix Panchang — Development Plan

**Supersedes:** the 2026-08-13 plan (its Phase 0–3 tasks — LocationChangeTest, AdhikaMasaBoundaryTest, SankrantiMomentTest, PHPStan fixes, Purnimanta, Kshaya, Raman/KP ayanamsha, parallax, EngineFactory, PSR-16 cache — are all done; that plan was based on the stale review).
**Based on:** `CODE_REVIEW_REPORT.md` (regenerated 2026-10-11), with every finding re-verified against the working tree on 2026-10-11.
**Baseline:** HEAD = v2.4.0-era commits; working tree holds the complete, uncommitted Milestone 8 accuracy harness (Swiss-Ephemeris fixtures, TransitionFinder/LunarEventCalculator rewrites, `composer accuracy` scripts). PHPStan level 8 clean; PHPUnit 335 tests / 2050 assertions green (`composer test`); accuracy group self-skips without fixtures.

**Corrections found during verification (vs the review report):**

- `solarmonth.php` now exists for **all 10 languages** — the report's localization-completeness gap is already closed. Only "translator not wired into output" remains.
- The uncommitted Milestone 8 work is **complete and coherent** (docs, changelog, tests, scripts all in place) — it just needs committing.
- `phpunit.xml` declares an Accuracy testsuite with `failOnWarning=true`, but `tests/Accuracy/` is untracked → **a fresh clone currently fails `composer test`**. This makes "commit first" the top priority.

---

## Design decisions (rationale)

- **D1 — PanchangService owns a `Panchang`.** `createDefault()` (`src/Panchanga/PanchangService.php:61`) builds a `Panchang` and discards it, then hand-wires a second engine. Refactor so the service owns one `Panchang::createDefault()` graph and delegates; preserve the 3 existing public methods byte-for-byte (all 8 service-consuming test files use only `createDefault`/`getForDate`/`getAstronomyReport`), then add explicit pass-throughs for the 14 facade methods (`src/Panchang.php`). Magic `__call` fights PHPStan 8 and IDE discoverability; the minimal fix alone leaves the REST-API gap open.
- **D2 — Bounded LRU cache, not plain deletion of `clearCache()`.** CHANGELOG v2.4.0 says the per-call clear (`PanchangService.php:148-150`) was a deliberate memory-leak fix for batch generation. Replace with a size-capped LRU (~512 entries, key = date + location hash + engine config); keep `clearCache()` public as an escape hatch. Guards: existing `LocationChangeTest` (cache-key correctness) + new `BatchMemoryTest` (the original leak).
- **D3 — Delete `findSankranti` (+ `bisectSankranti`, unused `$factory`).** Zero callers; `getAstronomyReport` inlines its own tested sankranti logic, so wiring it in would be a behavior change requiring fresh Swiss validation. Deletion is zero-risk; git history preserves it.
- **D4 — Ayanamsha realignment: yes, but as its own versioned commit after M8 lands.** Commit the harness first (parity established at current values), then one commit = corrected `LAHIRI_J2000` + nutation policy + fixture regeneration + `composer accuracy` green (per the `docs/accuracy.md` versioning policy).
- **D5/D6 — Commit-first ordering.** Until `tests/Accuracy/`, `tools/`, `tests/fixtures/reference/`, `docs/accuracy-report.md` and `src/Panchanga/TransitionSearchException.php` are committed, the repo is unclonable-green and every refactor risks losing the oracle baseline. Hygiene lands in the same breath, before any code change.
- **D7 — Priority ranking.** Eclipses (standard Panchang content; reference impls already in `scratch/`) > Pratyantardasha (`DashaPeriod` already supports nested `subPeriods` — nearly free) > Yoga/Dignity expansion (cheap, user-visible) > deep Shadbala (largest effort, least consumer demand → last, split in two).

---

## Phase 0 — Protect & Commit Current State (0.5–1 day)

**Goal:** green-from-fresh-clone; Milestone 8 protected in git history before any refactor.

| ID | Task | Effort | Acceptance criteria | Key files |
|----|------|--------|---------------------|-----------|
| T0.1 | Update `.gitignore` for assistant-workspace files (`.agents/`, `.openclaw*/`, `.commandcode/`, `AGENTS.md`, `HEARTBEAT.md`, `IDENTITY.md`, `SOUL.md`, `TOOLS.md`, `USER.md`, `composer.phar`; the 9 files already tracked under `scratch/` may stay) | 0.5h | `git status` after commit shows only intended files | `.gitignore` |
| T0.2 | Commit Milestone 8 (accuracy harness + rewrites) as 1–2 logical commits | 1h | `tests/Accuracy/*`, `tools/`, `tests/fixtures/reference/`, `docs/accuracy*.md`, `src/Panchanga/TransitionSearchException.php`, rewritten `TransitionFinder.php` + `LunarEventCalculator.php`, `tests/Unit/Panchanga/TransitionFinderTest.php`, composer/phpunit/phpstan config all committed; nothing unrelated swept in | — |
| T0.3 | Fresh-clone verification | 0.5h | Clean `git clone` + `composer install`: `composer test` (335 green), `composer analyse` (0 errors), `composer accuracy` green | — |
| T0.4 | Root hygiene: remove `ascendant.patch`; triage the 7 root scratch scripts (`check_month.php`, `debug_month.php`, `find_adhika.php`, `find_chaturthi.php`, `update_oracle.php`, `random_test_results.php`, `test_sign.php`) | 1h | Root contains only intended files | repo root |
| T0.5 | CHANGELOG entry for the M8 commit | 0.5h | Harness, rewrites, new scripts documented | `CHANGELOG.md` |

**Dependencies:** none. **Risk if skipped:** fresh clones fail `composer test`; the harness can be lost; refactors have no oracle baseline.

---

## Phase 1 (M9) — Service Layer & Cache Correctness → **v2.5.0** (~3.5–4 days)

**Goal:** one object graph behind `PanchangService`, bounded memory for batch generation, dead code gone. This is the phase the REST API builds on.

| ID | Task | Effort | Acceptance criteria | Key files |
|----|------|--------|---------------------|-----------|
| T1.1 | `PanchangService` owns a `Panchang` (D1): delegate `getForDate`/`getAstronomyReport` through it | 1d | Exactly one engine graph built (no discarded `Panchang` at :61, no hand-wiring at :65); 3 existing signatures unchanged; all 8 service-consuming test files green; `composer analyse` clean | `src/Panchanga/PanchangService.php`, `src/Panchang.php` |
| T1.2 | Facade pass-throughs on the service: `day`, `moment`, `janmarashi`, `festivals`, `muhurta`, `electional`, `calendar`, `ascendant`, `kundali`, `vimshottariDasha`, `varga`, `shadbala`, `yogas` | 1d | Each a one-line delegation; parity test vs direct `Panchang` calls; docblock updated to the true API surface | `src/Panchanga/PanchangService.php` |
| T1.3 | Bounded LRU cache replacing per-call `clearCache()` (D2) | 1d | `tests/Integration/Panchanga/LocationChangeTest.php` green; new `BatchMemoryTest`: ≥400 consecutive days, peak <100 MB, flat across 2 passes; second 30-day pass measurably faster; `clearCache()` still forces recomputation | `src/Panchanga/PanchangService.php`, `src/Astronomy/Engine/PurePhpAstronomyEngine.php` (`clearCache` :240), new `tests/Integration/Panchanga/BatchMemoryTest.php` |
| T1.4 | Delete `AdhikaMasaCalculator::findSankranti()` + private `bisectSankranti()` + unused `$factory` (D3) | 0.5h | Grep shows zero references; `composer test` green | `src/Calendar/AdhikaMasaCalculator.php` (:53-83, :99-124) |
| T1.5 | Remove stale imports (`src/Panchang.php:8,16-18`; `PanchangService.php:14-15`) | 0.25h | No unused `use` in either file; `composer analyse` clean | `src/Panchang.php`, `src/Panchanga/PanchangService.php` |
| T1.6 | Purnimanta consolidation: inject `PurnimantaMonthCalculator` into `PanchangaCalculator::forDay()` (no `new` per call, :172); property test proving both implementations agree on ≥1000 random dates, or make one canonical | 0.5–1d | `PurnimantaSystemTest` green; agreement test committed; the Krishna-detection divergence (prevFullMoon vs prevNewMoon) documented or eliminated | `src/Panchanga/PanchangaCalculator.php`, `src/Calendar/PurnimantaMonthCalculator.php`, `src/Calendar/HinduCalendarCalculator.php` (:108-130) |
| T1.7 | Release v2.5.0 | 0.5h | Tag; CHANGELOG notes the API surface + cache behavior change | `CHANGELOG.md` |

**Dependencies:** Phase 0. **Risk:** T1.3 is the one behavioral change — mitigated by LocationChangeTest + new BatchMemoryTest + retained `clearCache()`.

---

## Phase 2 (M10) — Accuracy & Ayanamsha Realignment → **v2.6.0** (~3–3.5 days)

**Goal:** close the 12-arcsec Lahiri bias safely; extend the harness to all three ayanamshas; CI runs everything.

| ID | Task | Effort | Acceptance criteria | Key files |
|----|------|--------|---------------------|-----------|
| T2.1 | Lahiri realignment (D4): correct `LAHIRI_J2000` to the IAE/Swiss mean value + decide the Δψ nutation policy. **One commit:** constant + `tools/reference/generate_fixtures.py` regeneration + versioned docs note | 1d | `composer accuracy` green with regenerated fixtures in the same commit; Lahiri residual vs Swiss ≤ ~2″ (from ~12″); versioned `docs/accuracy.md` entry per its policy; CHANGELOG migration note (sidereal outputs shift ~12″; tithi/yoga/karana limb math unaffected — tropical elongation) | `src/Astronomy/Sidereal/LahiriAyanamsha.php` (:36), `tools/reference/generate_fixtures.py`, `tests/fixtures/reference/`, `docs/accuracy.md` |
| T2.2 | Add `SE_SIDM_RAMAN` + `SE_SIDM_KRISHNAMURTI` datasets to the harness; upgrade `AyanamshaTest` from J2000 spot-checks to dataset comparisons | 1d | `composer accuracy` covers all 3 ayanamshas; `composer accuracy:report` shows per-ayanamsha residuals within `tests/Accuracy/Tolerances.php` | `tools/reference/generate_fixtures.py`, `tests/fixtures/reference/`, `tests/Integration/Astronomy/AyanamshaTest.php` |
| T2.3 | CI: GitHub Actions — `composer quality` + `composer accuracy` on PHP 8.2/8.3/8.4 | 0.5d | Green matrix run; badge in README | `.github/workflows/ci.yml`, `README.md` |
| T2.4 *(stretch)* | Address top residuals from `accuracy:report`, or document them as irreducible with cause | 1d | Residuals reduced or documented | per findings |
| T2.5 | Release v2.6.0 | 0.5h | Tag + CHANGELOG migration note | `CHANGELOG.md` |

**Dependencies:** Phase 0; T2.1 before T2.2 (Raman/KP baselines generated against the final constant).

---

## Phase 3 (M11) — Localization & Classical Completeness → **v2.7.0** (~5.5–7 days)

**Goal:** ship the built-but-unwired localization layer; complete the cheap classical gaps (dignity, yogas); begin Shadbala.

| ID | Task | Effort | Acceptance criteria | Key files |
|----|------|--------|---------------------|-----------|
| T3.1 | Wire localization: inject `TranslatorInterface` into `PanchangDay`/`PanchangResult` `toArray()/toJson()` (default English = current behavior, BC preserved); add a locale option to service pass-throughs | 1.5d | Default output byte-identical (BC test); `locale: 'hi'` returns Hindi for all 9 categories; test loads 10 languages × 9 category files (`bn en gu hi kn ml mr sa ta te`) | `src/ValueObject/PanchangDay.php` (:83-105), `src/ValueObject/PanchangResult.php` (:91-116), `src/Localization/ArrayTranslator.php`, `src/Panchanga/PanchangService.php` |
| T3.2 | Dignity friendship matrix (permanent + compound rule); Friendly/Neutral/Enemy return real `Dignity` cases | 1d | All 9 planets; unit matrix over lordship pairs; docblock (:19-20) updated; only documented exceptions return null | `src/Astrology/DignityCalculator.php`, `src/Enum/Dignity.php` |
| T3.3 | Yoga expansion: Parvata, Adhi, Kemadruma, Chandra-Mangala, Amala, Dhwaja (+ others) via one class per `YogaRuleInterface` + registration | 1.5d | ≥12 yogas detected; each with a classical example-chart unit test; registration pattern unchanged | `src/Astrology/YogaDetector.php` (:28-35), new `src/Astrology/Yoga/*` |
| T3.4a | Shadbala pt 1: SthanaBala completion (SaptaVargaja, Ojayugma, Navamsa/Drekkana) + real ChestaBala (speed/Seeghrocha) | 1.5d | `ChestaBalaCalculator` no longer returns 15.0 for all direct motion; components tested vs published reference values (B.V. Raman worked examples) | `src/Astrology/Shadbala/SthanaBalaCalculator.php`, `src/Astrology/Shadbala/ChestaBalaCalculator.php` |
| T3.4b *(may slip to M12)* | Shadbala pt 2: full KalaBala (replace the Ayana proxy, `ShadbalaCalculator.php:60`) + DrikBala via `AspectCalculator` (replace hardcoded 0.0, :65) | 2d | `drikBala` non-zero and derived from aspect strengths; KalaBala components (Nathonnatha…Yuddha) each unit-tested | `src/Astrology/ShadbalaCalculator.php`, `src/Astrology/AspectCalculator.php` |
| T3.5 | Release v2.7.0 | 0.5h | Tag + CHANGELOG | `CHANGELOG.md` |

**Dependencies:** T3.1 needs Phase 1's pass-throughs; T3.2–T3.4 are independent and can interleave.

---

## Phase 4 (M12) — Eclipses, Dasha Depth & REST Readiness → **v2.8.0** (~5–6 days)

**Goal:** the standard Panchang content that is missing (Grahan), plus the cheap astrology depth win (Pratyantardasha); declare the library REST-API-ready.

| ID | Task | Effort | Acceptance criteria | Key files |
|----|------|--------|---------------------|-----------|
| T4.1 | Solar eclipse calculator (adapt `scratch/astronomy-bundle/src/Events/SolarEclipse/` — cleaned, PHPStan-8, no scratch deps) | 2d | All solar eclipses 2024–2030 typed (total/annular/partial) vs the NASA canonical catalog; contact times within tolerance for 3+ reference eclipses | `src/Astronomy/Events/Eclipse/SolarEclipseCalculator.php` |
| T4.2 | Lunar eclipse calculator (port Meeus ch. 54 from `scratch/meeus/v3/eclipse/eclipse.go`) | 1.5d | Penumbral/partial/total 2024–2030 vs NASA catalog (e.g. 2025-03-14, 2025-09-07 totals) | `src/Astronomy/Events/Eclipse/LunarEclipseCalculator.php` |
| T4.3 | Grahan integration: `PanchangService::eclipsesForYear()` + per-day eclipse flag in `getForDate()` output | 0.5d | Day output marks eclipse days; year listing matches NASA for a 5-year span; documented | `src/Panchanga/PanchangService.php` |
| T4.4 | Pratyantardasha: third recursion level in `VimshottariDashaCalculator` (`DashaPeriod.subPeriods` already supports nesting) | 0.5d | Pratyantardasha spans sum to their Antardasha; 120-year invariant preserved; 3-level tree returned | `src/Astrology/VimshottariDashaCalculator.php` |
| T4.5 | REST-readiness pass: JSON-serializable returns everywhere; `docs/api.md` covering the full surface; `UPGRADE.md` for v2.5→v2.8 | 1d | `json_encode` succeeds on every service return; docs complete | `src/Panchanga/PanchangService.php`, `docs/api.md`, `UPGRADE.md` |
| T4.6 | Release v2.8.0 | 0.5h | Tag + CHANGELOG + release notes | `CHANGELOG.md` |

**Dependencies:** T4.3 needs T4.1+T4.2; T4.5 needs Phase 1. v3.0.0 (SDKs, per `ROADMAP.md`) is explicitly out of scope for this plan.

---

## Timeline (single developer, ~6 weeks)

| Week | Focus |
|------|-------|
| 1 | Phase 0 + T1.1, T1.4, T1.5 |
| 2 | T1.2, T1.3, T1.6 → **v2.5.0** |
| 3 | T2.1–T2.3 → **v2.6.0** |
| 4 | T3.1, T3.2, T3.3 |
| 5 | T3.4a (+T3.4b if velocity allows) → **v2.7.0**; start T4.1 |
| 6 | T4.1–T4.6 → **v2.8.0**; buffer |

---

## Definition of Done (per task)

- [ ] Unit and/or integration tests added or updated
- [ ] `composer test` green (335+ tests)
- [ ] `composer analyse` green (PHPStan level 8, 0 errors)
- [ ] `composer accuracy` green if any astronomy-path file was touched
- [ ] `composer accuracy:report` reviewed if fixtures changed
- [ ] No regression in `tests/Integration/MultiLocationOracleTest.php` and `tests/Integration/Panchanga/LocationChangeTest.php`
- [ ] Documentation updated if user-facing (docs/, UPGRADE.md)
- [ ] CHANGELOG.md entry added
- [ ] Working tree committed; no unrelated files swept into the commit

---

## Risk mitigation

| Risk | Mitigation |
|------|------------|
| Removing the per-call clear reintroduces the v2.4.0 memory leak | LRU cap bounds memory structurally; new `BatchMemoryTest` runs in `composer test`; `clearCache()` retained as escape hatch |
| Cache returns stale/wrong data across locations or ayanamshas | Cache key includes date + location hash + engine config; `LocationChangeTest` is the canary on every cache-touching commit |
| Ayanamsha shift surprises downstream consumers | Versioned per the `docs/accuracy.md` policy; fixtures regenerated in the SAME commit; CHANGELOG quantifies the ~12″ shift and states limb math (tithi/yoga/karana) is unaffected; shipped as minor v2.6.0, not a patch |
| Purnimanta consolidation picks the wrong canonical implementation | Property test proving agreement over ≥1000 random dates BEFORE any deletion; `PurnimantaSystemTest` stays green throughout |
| Eclipse porting errors (scratch code may be unpolished) | Validate against the NASA canonical eclipse catalog 2024–2030, not self-derived expectations; verify 3+ known eclipses with published circumstances |
| Shadbala scope creep (largest item in plan) | Split T3.4a/T3.4b; T3.4b explicitly allowed to slip to M12; partial components flagged in docs until complete |
| Fixtures bloat the repo | Small text files; check `git count-objects -vH` before/after Phase 0; the generation script keeps them reproducible |
| Assistant-workspace files accidentally committed | `.gitignore` updated in T0.1 BEFORE the first commit; `git status` reviewed in T0.2 acceptance |

---

## Success metrics

| Metric | Current | Target (v2.8.0) |
|--------|---------|-----------------|
| Tests / assertions | 335 / 2050 | ≥ 420 / ≥ 2500 |
| Fresh clone passes `composer test` | No (untracked Accuracy suite) | Yes |
| Ayanamshas covered by accuracy harness | 1 (Lahiri) | 3 (Lahiri, Raman, KP) |
| Lahiri residual vs Swiss Ephemeris | ~12 arcsec | ≤ ~2 arcsec |
| PHPStan | 0 errors @ level 8 | 0 errors @ level 8 |
| Batch memory (365 days) | Unbounded without per-call clear | < 100 MB, bounded LRU, no per-call clear |
| Yogas detected | 6 | ≥ 12 |
| Dignity results | Only Exalted/Debilitated non-null | All cases per friendship matrix |
| Shadbala | drikBala = 0.0, kalaBala = proxy | All 6 balas computed |
| Localization wired | 0 call sites of ArrayTranslator | 10 languages served via locale option |
| Eclipses | Absent | Solar + lunar, NASA-verified 2024–2030 |
| Dasha levels | 2 | 3 (Mahadasha/Antardasha/Pratyantardasha) |
| CI | None | GitHub Actions, PHP 8.2/8.3/8.4, `quality` + `accuracy` |

---

## Immediate next steps

1. **T0.1** — update `.gitignore` (assistant-workspace files, `composer.phar`).
2. **T0.2** — commit Milestone 8 (`tests/Accuracy/`, `tools/`, `tests/fixtures/reference/`, `docs/accuracy*.md`, `src/Panchanga/TransitionSearchException.php`, `tests/Unit/Panchanga/TransitionFinderTest.php`, the rewrites, composer/phpunit/phpstan config); verify `git status` shows nothing unintended.
3. **T0.3** — fresh clone → `composer install && composer test && composer analyse && composer accuracy`; do not proceed until green.
4. **T0.4** — remove `ascendant.patch`; triage the seven root-level scratch scripts.
5. Branch `refactor/service-layer`, start T1.1 (PanchangService owns `Panchang::createDefault()`), then T1.3 (bounded LRU cache) — the two highest-value, highest-risk items while the harness is fresh.

---

*Plan reviewed against: CODE_REVIEW_REPORT.md (regenerated 2026-10-11) | Working tree verified 2026-10-11 | Adjust weekly based on velocity*
