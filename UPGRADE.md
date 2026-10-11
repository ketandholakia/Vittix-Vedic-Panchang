# Upgrade Guide

## Upgrade to v2.5.0

### What Changed
- `PanchangService` now exposes the full `Panchang` facade surface as pass-throughs
  (`day()`, `moment()`, `kundali()`, `vimshottariDasha()`, `varga()`, `shadbala()`, `yogas()`,
  `festivals()`, `muhurta()`, `electional()`, `calendar()`, `ascendant()`, `janmarashi()`).
  All existing methods (`createDefault()`, `getForDate()`, `getAstronomyReport()`) are unchanged.
- Results from `getForDate()` / `getAstronomyReport()` are memoised in a bounded LRU cache
  (512 entries, keyed by date, timezone, location and month system). Repeat calls with
  identical inputs return the memoised result. The per-call cache clearing of v2.4.0 is gone.
- `PanchangService::clearCache()` clears the result cache and the engine's in-memory caches
  when you need to force recomputation. The optional PSR-16 persistent cache is never
  cleared by this method.
- `PurePhpAstronomyEngine` accepts an optional `memoryCacheCapacity` constructor argument
  (default 512) if you want to trade memory for cold-start throughput.

### Compatibility Notes
- Existing code requires no changes. `createDefault()` now constructs exactly one engine
  graph instead of two.
- Batch consumers that relied on v2.4.0's per-date cache clearing for memory containment
  no longer need it: memory is bounded structurally (measured: +2.3 MB over a 400-day batch).

### Recommended Steps
1. Update your composer dependency: `composer update vittix/panchang`.
2. If you call `clearCache()` (engine or service) periodically in long-running jobs, you may
   remove those calls — bounded eviction handles it — or keep them to reclaim memory at
   explicit points.
3. Run your existing test suite after upgrading.

## Upgrade to v2.0.0

This release introduces the first full Vedic astrology support in addition to Panchang calculations.

### PHP Requirements
- PHP 8.2 or newer is required.

### What Changed
- Added Kundli (natal chart) generation.
- Added Ascendant (Lagna) position calculations.
- Added mean lunar node support for Rahu and Ketu.
- Added astrology helpers such as house, rashi, and yoga calculations.

### Compatibility Notes
- Existing Panchang APIs remain stable.
- New astrology APIs are additive and do not require changes to existing Panchang usage.

### Recommended Steps
1. Update your composer dependency: `composer update vittix/panchang`.
2. Review the new `Panchang::kundali()`, `Panchang::ascendant()`, and related astrology APIs.
3. Run your existing test suite after upgrading.
