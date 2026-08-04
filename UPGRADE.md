# Upgrade Guide

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
