# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
