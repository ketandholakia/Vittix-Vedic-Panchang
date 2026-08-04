# 🚀 Vittix Vedic Panchang v2.0.0 – Astrology & Kundli Release

This is the biggest release of Vittix Vedic Panchang so far.

## Highlights

- Complete Vedic Astrology and Kundli engine
- Birth chart generation with Ascendant (Lagna)
- Planetary positions for the traditional seven bodies
- Whole-sign house placement and Rashi determination
- Divisional charts (Vargas) and Vimshottari Dasha support
- Improved documentation and guided examples
- Official demo site: https://hindutithi.in/

## ✨ Added

- `Body::Mercury`, `Body::Venus`, `Body::Mars`, `Body::Jupiter`, `Body::Saturn`
- `Body::RahuMean` and `Body::KetuMean`
- `Astrology/DignityCalculator`
- `Astrology/AspectCalculator`
- `Astrology/KundaliCalculator`
- `Astrology/VimshottariDashaCalculator`
- `Astrology/ShadbalaCalculator`
- `YogaDetector`
- `Panchang::kundali()` for complete birth chart generation
- `Panchang::varga()` for divisional chart projections
- `Panchang::vimshottariDasha()` for planetary period sequences
- `Astronomy/Sidereal/SiderealTimeCalculator`
- `Astronomy/Coordinates/AscendantCalculator`
- `Panchang::ascendant()` for Lagna calculation
- `SECURITY.md`, `CODE_OF_CONDUCT.md`, `ROADMAP.md`, `UPGRADE.md`
- docs: installation, quick-start, panchang, kundli, astrology, api, accuracy, examples

## 🚀 Improved

- Redesigned `README.md` for a broader Panchang + Astrology product message
- Updated `composer.json` metadata, keywords, homepage, and support fields
- Better release documentation and developer guidance

## 🐞 Fixed

- No breaking issues were introduced in this release. The update is additive and focused on new astrology capabilities.

## 💥 Breaking Changes

- None.

## ⬆️ Upgrade

```bash
composer update vittix/panchang
```

## 📖 Full changelog

See `CHANGELOG.md` for the detailed list of changes from v1.1.0 to v2.0.0.
