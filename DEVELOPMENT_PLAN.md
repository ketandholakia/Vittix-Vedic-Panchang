# Vittix Panchang — Development Plan

Based on: `CODE_REVIEW_REPORT.md` | Generated: 2026-08-13

---

## Phase 0 — Protect Current Behavior (Week 1)
**Goal**: Establish regression safety net before any refactoring

| Task | Owner | Effort | Acceptance Criteria |
|------|-------|--------|---------------------|
| **T0.1** Add `LocationChangeTest` | | 2h | Test passes: same date, Ahmedabad vs Delhi → different sunrise/tithi-end |
| **T0.2** Add `AdhikaMasaBoundaryTest` | | 3h | Tests 2023 Shravana Adhika + transitions (day before/after) |
| **T0.3** Add `SankrantiMomentTest` | | 3h | Makara & Mesha Sankranti within 60s of Meeus reference |
| **T0.4** Fix all 26 PHPStan Level-8 errors | | 4h | `composer analyse` passes clean |
| **T0.5** Capture golden snapshots for CI | | 1h | `tests/fixtures/generated/panchang_snapshots.json` committed |

**Dependencies**: None — can start immediately  
**Risk if skipped**: Silent regressions during Phases 1-3

---

## Phase 1 — Correctness Enhancements (Weeks 2-3)
**Goal**: Close functional gaps vs. traditional Panchang requirements

| Task | Owner | Effort | Acceptance Criteria |
|------|-------|--------|---------------------|
| **T1.1** Implement `PurnimantaMonthCalculator` | | 1.5d | New `monthSystem` option in `PanchangService::getForDate()`; North India dates match Kalnirnay |
| **T1.2** Add Kshaya Masa detection | | 2d | Detects two Sankrantis in one lunation; documented with 19-yr cycle test |
| **T1.3** Add Raman & Krishnamurti ayanamsha | | 1d | `AyanamshaCalculatorInterface` implementations; selectable via `Panchang::create()` |
| **T1.4** Add solar parallax correction for elevation | | 0.5d | Sunrise at 5000m elevation shifts by ~1s vs current |
| **T1.5** Document supported date range (1900-2100) | | 0.5d | `docs/accuracy.md` updated with precision table |

**Dependencies**: Phase 0 complete  
**Rollback**: Each task behind feature flag; main branch stays green

---

## Phase 2 — Architecture Improvements (Weeks 3-4)
**Goal**: Eliminate technical debt, improve extensibility

| Task | Owner | Effort | Acceptance Criteria |
|------|-------|--------|---------------------|
| **T2.1** Extract `EngineFactory` | | 1d | `PanchangService::createDefault()` delegates to factory; zero wiring duplication |
| **T2.2** Break `LunarEventCalculator` circular dependency | | 0.5d | New `LunarPositionProvider` interface; engine no longer passes itself |
| **T2.3** Add optional PSR-16 cache for batch ops | | 1d | `PurePhpAstronomyEngine` accepts `CacheInterface`; 1-year calendar < 3s |
| **T2.4** Make `DeltaT` source configurable | | 0.5d | `DeltaTProvider` interface; polynomial (default) vs IERS table |

**Dependencies**: Phase 1 tasks independent; can parallelize  
**Risk**: Medium — touches core wiring; needs full test suite pass

---

## Phase 3 — Testing Expansion (Weeks 4-5)
**Goal**: Achieve high-confidence multi-location, multi-tradition coverage

| Task | Owner | Effort | Acceptance Criteria |
|------|-------|--------|---------------------|
| **T3.1** Complete multi-location oracle matrix | | 2d | 10+ locations × 5 key dates validated vs Swiss Ephemeris |
| **T3.2** Add Purnimanta vs Amanta comparison tests | | 1d | Same date, both systems, month names differ correctly |
| **T3.3** Add property-based limb boundary tests | | 1d | QuickCheck-style: random dates, verify index transitions monotonic |
| **T3.4** CI matrix: PHP 8.2, 8.3, 8.4 | | 0.5d | GitHub Actions runs all versions; badge in README |

**Dependencies**: Phase 1 (Purnimanta) for T3.2  
**Data source**: Swiss Ephemeris `swetest` for oracle generation

---

## Phase 4 — Performance & Polish (Week 5+)
**Goal**: Production hardening, developer experience

| Task | Owner | Effort | Acceptance Criteria |
|------|-------|--------|---------------------|
| **T4.1** Profile & optimize batch generation | | 1d | 365-day calendar < 2s with cache; memory < 50MB |
| **T4.2** Brent's method for transition finder | | 1d | 30-50% fewer iterations vs bisection; same 5s tolerance |
| **T4.3** Precomputed planetary tables (optional) | | 2d | VSOP87 terms cached to disk; cold-start < 100ms |
| **T4.4** Expand `docs/` with algorithm references | | 1d | Meeus chapter refs, precision tables, limitation disclosures |
| **T4.5** Add `TestLocations` for 10 major cities | | 0.5d | Ahmedabad, Delhi, Mumbai, Kolkata, Chennai, Varanasi, Ujjain, Haridwar, London, NY |

**Dependencies**: Phase 2 (cache) for T4.1  
**Nice-to-have**: T4.3 only if batch perf critical

---

## Resource Allocation (Single Developer)

| Week | Mon | Tue | Wed | Thu | Fri |
|------|-----|-----|-----|-----|-----|
| 1 | T0.1 | T0.2 | T0.3 | T0.4 | T0.5 |
| 2 | T1.1 | T1.1 | T1.2 | T1.2 | T1.3 |
| 3 | T1.4 | T1.5 | T2.1 | T2.1 | T2.2 |
| 4 | T2.3 | T2.3 | T2.4 | T3.1 | T3.1 |
| 5 | T3.2 | T3.3 | T3.4 | T4.1 | T4.2 |
| 6 | T4.3* | T4.3* | T4.4 | T4.5 | Buffer |

*Optional — only if batch perf is a product requirement

---

## Definition of Done (Per Task)

- [ ] Unit tests added/updated
- [ ] Integration tests pass (`composer test`)
- [ ] Static analysis passes (`composer analyse`)
- [ ] No regression in oracle tests
- [ ] Documentation updated if user-facing
- [ ] CHANGELOG.md entry added

---

## Release Milestones

| Milestone | Target | Contents |
|-----------|--------|----------|
| **v2.2.0** | End of Week 3 | Purnimanta, Kshaya Masa, Raman/Krishnamurti ayanamsha |
| **v2.3.0** | End of Week 4 | EngineFactory, PSR-16 cache, DeltaT provider |
| **v2.4.0** | End of Week 6 | Full test matrix, Brent optimizer, docs, multi-city |

---

## Risk Mitigation

| Risk | Mitigation |
|------|------------|
| Phase 1 breaks existing Amanta dates | Feature flag `month_system=amanta|purnimanta`; default unchanged |
| EngineFactory introduces wiring bugs | Keep `Panchang::createDefault()` unchanged; new factory returns identical object graph |
| PSR-16 cache adds dependency | Optional constructor arg; `null` = current in-memory behavior |
| Oracle tests fail after changes | Run oracle suite before every commit; bisect to isolate |

---

## Success Metrics

| Metric | Target |
|--------|--------|
| Test count | ≥ 350 (currently 292) |
| Oracle locations | ≥ 10 (currently 3) |
| PHPStan errors | 0 at level 8 |
| Batch calendar (365 days) | < 3s with cache |
| Oracle time tolerance | ≤ 60s (currently 120s) |
| Documentation coverage | All public classes have PHPDoc + Meeus ref |

---

## Immediate Next Steps (Today)

1. **Create `LocationChangeTest`** — highest ROI regression guard
2. **Run `composer analyse`** — catalog the 26 errors
3. **Branch `phase-0-foundation`** — start Phase 0 work
4. **Schedule Swiss Ephemeris access** — for T3.1 oracle generation

---

*Plan reviewed against: CODE_REVIEW_REPORT.md | Adjust weekly based on velocity*