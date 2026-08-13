# Accuracy and Supported Date Range

Vittix Panchang uses a highly optimized pure-PHP astronomical engine designed for standard astrological and calendrical computations.

## Supported Date Range

**The officially supported and tested date range is 1900–2100 AD.**

Calculations outside this range will still execute without throwing errors, but the precision degrades exponentially.

### Why 1900–2100?
1. **Delta T Approximations**: The time difference between Terrestrial Time (TT) and Universal Time (UT) is known as Delta T ($\Delta T$). This engine uses the standard polynomial approximations from Espenak and Meeus for the 20th and 21st centuries. For dates outside this range (especially historical dates before 1600 AD), these polynomials diverge from historical eclipse records.
2. **Orbital Perturbations**: A high-precision engine (like VSOP87 or JPL DE431) evaluates thousands of periodic terms for planetary orbits. For extreme speed in pure PHP, this engine evaluates a truncated series of periodic terms (usually those > 0.0001 degrees) and assumes linear/quadratic changes to orbital elements. These truncated terms are highly accurate near J2000.0 but drift over millennia.
3. **Nutation & Precession**: The IAU nutation models and precession matrices used here are linearized around J2000.0.

## Expected Precision (1900–2100)

Within the supported range, the engine provides the following precision compared to JPL DE-based Swiss Ephemeris:

| Calculation | Typical Error | Max Error | Implication for Panchang |
| --- | --- | --- | --- |
| Sun Longitude | < 0.0003° (1") | 0.0008° | Negligible (~2 seconds drift in Sankranti) |
| Moon Longitude | < 0.002° (7") | 0.005° (18") | Minor (~10 seconds drift in Tithi boundaries) |
| Lunar Node (Rahu/Ketu) | < 0.001° | 0.002° | Negligible |
| Planets (Mercury-Saturn) | < 0.005° | 0.02° | Negligible for house placements, slight drift in exact aspect timings |
| Ayanamsha (Lahiri/Raman/KP) | Exact | Exact | Matches standard polynomial definitions precisely |

### Near-Boundary Warnings
Panchang limbs (Tithi, Nakshatra, etc.) are discrete periods. Because there is a very small computational variance (e.g. ±10 seconds for a Tithi end time), if a limb ends exactly at sunrise (within a few seconds), the engine might flip the *active* limb for that day.

The engine detects this automatically and sets the `accuracy->nearBoundary` flag to `true` when a transition happens within 2 minutes of sunrise, warning you that the calculated result is sensitive to minute computational differences or location coordinate precision.

### Horizon Dip and Elevation
Sunrise and Sunset calculations incorporate standard atmospheric refraction and horizon dip based on observer elevation.
- Standard altitude `h0 = -0.8333°`
- Dip correction `= 1.92 * sqrt(elevation_in_meters)` arcminutes

This ensures accuracy for mountainous locations where sunrise occurs earlier than at sea level.

## Architecture

Key accuracy features:
- Planetary positions using truncated VSOP87 series
- Nutation and true obliquity calculations
- Sidereal conversions with configurable ayanamsa (Lahiri, Raman, Krishnamurti)
- Ascendant longitude computed using Meeus formulas
- Mean Rahu/Ketu lunar nodes calculated from nutation terms

This package is designed to avoid external binaries, network dependencies, or native extensions while still providing sub-arcminute accuracy for contemporary astrology.
