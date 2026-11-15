# Upgrade Guide

## Upgrade to v3.0 (migration from 2.x)

v3.0 contains **breaking changes** introduced by the 2026-11 defect round. All are
mechanical; the numeric/behavioral fixes behind them are listed in CHANGELOG.md.

### Tithi case renames
- The `ShuklaD<n>`/`KrishnaD<n>` spellings are replaced by consistent Sanskrit case names:
  `ShuklaDwitiya`, `ShuklaTritiya`, `ShuklaChaturthi`, `ShuklaPanchami`, `ShuklaShashthi`,
  `ShuklaSaptami`, `ShuklaNavami`, `ShuklaDashami`, `ShuklaEkadashi`, `ShuklaDwadashi`,
  `ShuklaTrayodashi` (and the Krishna counterparts).
- The old spellings still work: they remain as `@deprecated` class constants bound to the
  identical canonical case, so `Tithi::ShuklaD3 === Tithi::fromIndex(2)` stays true.
- Note that `->name` (and localization lookups by case name) now return the canonical
  name (`ShuklaTritiya`, not `ShuklaD3`).
- Action: replace usages at your leisure; localize from the bundled tables which contain
  both spellings, or add your canonical keys.

### `AyanamshaCalculatorInterface::id()` is a new required method
- Custom ayanamsha implementations must add `public function id(): string` returning a
  short stable identifier (e.g. `'lahiri'`, `'raman'`). It namespaces the PSR-16 cache
  keys so two engines with different systems cannot share each other's cached values.

### `FestivalRuleInterface` redesigned
- `isObserved(DateTimeImmutable $date, GeoLocation $location, PanchangaCalculator
  $panchanga, mixed $context = null): bool` is replaced by
  `score(FestivalDayContext $context): float` — the fraction of the rule's kala window
  during which the target tithi prevails (0.0 = not observed). Custom rules must be
  rewritten against `FestivalDayContext` (or extend `Rules\TithiKalaRule`).

### `FestivalEngine` constructor change
- `new FestivalEngine($panchangaCalculator)` becomes
  `new FestivalEngine($astronomyEngine, $tithiCalculator, $lunarMonthCalculator)`.
- `FestivalCatalog::getDefaultFestivals()` no longer takes a `MuhurtaCalculator`.

### `ViddhaTithiRule` takes a `Kala` enum
- `new ViddhaTithiRule($month, $tithi, 'Nishita', $muhurtaCalculator)` becomes
  `new ViddhaTithiRule($month, $tithi, Kala::Nishita)` — one of `Sunrise`, `Madhyahna`,
  `Aparahna`, `Pradosh`, `Nishita` (`Enum\Kala`). `SunriseTithiRule` gains an optional
  `Kala` parameter (default `Sunrise`).

### `PurnimantaMonthCalculator::purnimantaMonth(month, paksha)` removed
- The paksha-based +1 shift was wrong around Adhika/Kshaya months. Use the new
  `monthAt(AstroInstant): Lunation`, which returns the month plus its Adhika/Kshaya
  flags. `LunarMonthCalculator::amantaLunation(newMoon)` provides the same
  classification for Amanta lunations.

### `getHoras()` signature change
- `getHoras(sunrise, nextSunrise, weekdayIndex)` becomes
  `getHoras(sunrise, sunset, nextSunrise, weekdayIndex, HoraMode $mode = HoraMode::Traditional)`.
  The default now returns the traditional 12 day + 12 night Horas; pass
  `HoraMode::EqualHours` for the old 24-equal-segment behavior.

### PSR-16 cache key format changed — flush persistent caches on upgrade
- Keys are now `vpanchang.pos.<body>.<jd>` and `vpanchang.ayan.<system>.<jd>` (the old
  `pos:…`/`ayan:…` keys used PSR-16 reserved characters and omitted the ayanamsha
  system). Stale entries under the old keys are simply never read again, but they will
  linger: **flush your persistent PSR-16 cache when upgrading** to reclaim the space.

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
