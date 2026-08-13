# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.4.0] - 2026-08-13

### Added
- Added `docs/algorithms.md` detailing the mathematical models, periodic truncations, and root-finding methodology used by the astronomical engine.
- Expanded `tests/Support/TestLocations.php` to include global major cities (Mumbai, Kolkata, Chennai, Bengaluru, New York, London, Tokyo, Sydney) for standardising oracle tests.

### Performance
- Fixed memory leaks in batch generation by capping `PanchangService` caching, keeping long-running 365-day loops strictly under `<50MB`.
- Replaced the initial 30-minute Bisection method in `TransitionFinder` with an adaptive step algorithm using Secant (linear) interpolation, drastically reducing the number of astronomical function evaluations.
- Inlined degrees-to-radians conversions (`0.01745...`) in `LunarPositionCalculator`, `SolarPositionCalculator`, and `NutationCalculator` inside high-frequency periodic calculation loops.
- Reduced overall batch runtime by 30%.

## [2.1.0] - 2026-08-07

### Added
- Phase 1 Core Foundation APIs for external integration.
- `PanchangService` class providing a composed engine for structured JSON API outputs (`getForDate()`, `getAstronomyReport()`).
- `AstronomyResult` and `PanchangResult` DTOs with JSON serialization support (`toJson()`, `toArray()`).
- `LunarEventCalculator` for accurate Moonrise/Moonset and transit calculations.
- `HinduCalendarCalculator` enhancements for Vikram Samvat, Shaka Samvat, and Gujarati calendar edge cases.
- Added extensive unit and integration tests against real-world references (USNO) and edge cases across multiple global locations.

## [2.0.0] - 2026-08-04

### Added
- Added `Body::Mercury`, `Body::Venus`, `Body::Mars`, `Body::Jupiter`, and `Body::Saturn` support in the astronomy engine, utilizing truncated VSOP87 theory to achieve highly accurate planetary positions without large data file dependencies.
- Added a shared `PlanetPositionCalculator` capable of computing apparent geocentric coordinates (with full light-time correction and precession/nutation) from the VSOP87 series. This lays the groundwork for remaining planets.
- Added `Body::RahuMean` and `Body::KetuMean` — mean lunar node positions,
  computed from the Ω term already used internally for nutation. Supported
  through the existing `AstronomyEngineInterface` (`position()`,
  `tropicalLongitude()`, `siderealLongitude()`) like any other body.
- Added `Astrology/DignityCalculator` to evaluate planetary strength (Exalted,
  Moolatrikona, Own Sign, Debilitated) and integrated it into the Kundali.
- Added `Astrology/AspectCalculator` to evaluate Planetary Aspects (Drishti).
  Integrated into the Kundali to calculate which houses a planet aspects.
- Added `Astrology/KundaliCalculator` to map planetary positions to Rashis
  (Zodiac signs) and calculate houses using the Whole-Sign House system.
- Added `Astrology/VimshottariDashaCalculator` to generate the 120-year sequence.
- Added `Astrology/ShadbalaCalculator` for computing the Six-fold Planetary
  Strength (Shadbala). Includes Naisargika (Natural), Dik (Directional), Sthana
  (Positional), Ayana (Equinoctial), and Chesta (Motional) Balas.
- Added a `YogaDetector` rules engine to scan birth charts for classical planetary
  combinations. Currently supports the Pancha Mahapurusha Yogas (Ruchaka, Bhadra,
  Hamsa, Malavya, Sasha) and Gajakesari Yoga.
- Exposed `shadbala()` and `yogas()` methods on the main `Panchang` facade.
- Expanded `AstronomyEngineInterface` with `equatorialPosition()` (Right Ascension/
  Declination) and `dailyMotion()` primitives.
- Added Divisional Charts (Vargas) generation (`Astrology/VargaCalculator`) with support for
  all 16 traditional Parashari charts (Shodashvargas): D1, D2, D3, D4, D7, D9, D10, D12,
  D16, D20, D24, D27, D30, D40, D45, and D60.
- Exposed a new `kundali()` method on the main `Panchang` facade for
  generating complete birth charts.
- Exposed a new `varga()` method on the main `Panchang` facade to get divisional charts.
- Exposed a new `vimshottariDasha()` method on the main `Panchang` facade.
- Added `Astronomy/Sidereal/SiderealTimeCalculator` (Greenwich/Local Mean
  Sidereal Time), extracted from a private method previously duplicated
  only inside `SolarEventCalculator`.
- Added `Astronomy/Coordinates/AscendantCalculator` for Ascendant (Lagna)
  longitude — first-house/whole-sign reference point for Kundali charts.
- Added `ValueObject\AscendantPosition` and `$panchang->ascendant(DateTimeImmutable $moment, GeoLocation $location): AscendantPosition`
  on the `Panchang` facade.

### Changed
- `SolarEventCalculator` now depends on the shared `SiderealTimeCalculator`
  instead of a private, duplicated GMST implementation. Behavior is
  unchanged (verified sunrise/sunset outputs identical before/after).

## [1.1.0] - 2026-08-01

### Added
- Added `.github/workflows/ci.yml` for automated testing and static analysis.
- Exposed `$panchang->calculator()` to retrieve the underlying `PanchangaCalculator`.
- Exposed `$panchang->festivals()` to initialize and return the `FestivalEngine` seeded with default festivals.
- Exposed `$panchang->muhurta()` to return a `MuhurtaCalculator`.
- Exposed `$panchang->electional()` to return an `ElectionalEvaluator`.
- Exposed `$panchang->calendar(DateTimeImmutable $date)` to return a `HinduCalendarResult` with Samvat and Adhika Masa details.
- Added comprehensive native-script localization files for 9 Indian languages (Hindi, Gujarati, Sanskrit, Marathi, Bengali, Tamil, Telugu, Kannada, Malayalam).
- Added localization support for Yoga, Karana, and Rashi components.

### Fixed
- Fixed inline FQCN for `janmarashi()` return type in `Panchang.php`.
- Fixed indentation in `SolarEventCalculator::refineEvent()`.
- Refined Sanskrit translations to use Devanagari script instead of IAST.
