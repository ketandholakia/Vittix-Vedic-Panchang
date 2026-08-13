# Astronomical Algorithms & Implementation

Vittix Panchang relies on high-precision algorithms to determine planetary positions and celestial events. This document provides references and technical disclosures on the computational methods used.

## Planetary Positions

### The Sun and Planets
The geocentric ecliptic positions of the Sun and planets (Mercury through Saturn) are calculated using truncated terms from the **VSOP87** (Variations Séculaires des Orbites Planétaires) mathematical theory, developed by Bretagnon and Francou (1988) at the Bureau des Longitudes, Paris.

- **Source Reference**: Jean Meeus, *Astronomical Algorithms* (2nd ed.), Chapter 32 (Planetary Positions) and Chapter 25 (Solar Coordinates).
- **Implementation**: The engine uses the most significant periodic terms (typically those > 0.0001°) to balance extreme calculation speed in pure PHP with sufficient accuracy for Panchang boundaries. 
- **Precision**: Better than 0.01° for the years 1900–2100.

### The Moon
Lunar positions are notoriously difficult to calculate due to complex gravitational perturbations by the Sun, Earth's oblate shape, and other planets. We use a truncated version of the **ELP2000-82** theory by Chapront-Touzé and Chapront.

- **Source Reference**: Jean Meeus, *Astronomical Algorithms* (2nd ed.), Chapter 47 (Position of the Moon).
- **Implementation**: We compute 60 periodic terms for longitude and 60 for latitude, alongside planetary perturbation corrections (Venus/Jupiter).
- **Precision**: Longitude error typically ≤ 10 arcseconds (0.003°), latitude ≤ 30 arcseconds (0.008°).

## Coordinate Systems and Corrections

### Nutation & Obliquity
Nutation in longitude (Δψ) and obliquity (Δε) are computed using the **IAU 1980 Theory of Nutation**.

- **Source Reference**: Jean Meeus, *Astronomical Algorithms* (2nd ed.), Chapter 22.
- **Implementation**: A 63-term truncated series.
- **Precision**: ~0.5 arcseconds.

### Sidereal Time
Mean Sidereal Time is calculated using the standard IAU polynomial for J2000.0. Local Mean Sidereal Time (LMST) is derived by adding the observer's geographic longitude.

### Ayanamsha (Precession of the Equinoxes)
Panchang limbs rely on sidereal (Nirayana) coordinates, requiring subtraction of the Ayanamsha from tropical (Sayana) longitudes.

- **Lahiri (Chitra Paksha)**: The standard Indian government ayanamsha. Fixed 0° near 285 AD, aligned with the star Spica.
- **Raman**: Proposed by B.V. Raman.
- **KP (Krishnamurti Paddhati)**: Used in KP Astrology.

*Note: All Ayanamsha values are calculated using standard precession formulas. True Ayanamsha differs from Mean Ayanamsha by the nutation in longitude (Δψ).*

## Event Discovery (Root Finding)

Panchang boundaries (like Tithi End, Nakshatra End) occur when the Sun and Moon reach specific relative angles. Finding the exact time of these events requires solving for the roots of continuous astronomical functions.

### Adaptive Secant Interpolation
To find the exact time a Tithi or Nakshatra ends, the engine uses **Secant/Brent's Method** combined with adaptive time-stepping.

1. **Fractional Jump**: Instead of stepping forward by a fixed interval (e.g. 30 minutes), the engine determines the fractional progress of the current limb (e.g. 0.8 / 1.0) and dynamically jumps time forward near the boundary.
2. **Root Refinement**: The secant method then interpolates the remaining fraction, rapidly converging on the exact second.
3. **Tolerance**: Convergence tolerance is strictly set to **5 seconds** of astronomical time.

## Limitations & Disclosures
1. **Delta T**: Calculations before 1600 AD or after 2200 AD rely on extrapolated $\Delta T$ polynomials. Since Earth's rotation slows unpredictably due to tidal friction and core interactions, times for historical or distant future events may drift.
2. **Extreme Latitudes**: For latitudes beyond the polar circles (e.g. > 66.5°), the Sun may not rise or set for extended periods. In these edge cases, `sunrise` and `sunset` may return `null`.
3. **No External Dependencies**: Unlike some libraries that wrap the SWISSEPH C-library (Swiss Ephemeris), Vittix Panchang runs entirely in native PHP. As a consequence, it uses truncated rather than full theories to maintain sub-second runtime performance.
