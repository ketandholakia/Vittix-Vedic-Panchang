# Astronomical Algorithms & Implementation

Vittix Panchang relies on high-precision algorithms to determine planetary positions and celestial events. This document provides references and technical disclosures on the computational methods used.

## Planetary Positions

### The Sun and Planets (Mercury through Saturn)

The geocentric ecliptic positions are calculated using truncated terms from the **VSOP87** (Variations Séculaires des Orbites Planétaires) mathematical theory, developed by Bretagnon and Francou (1988) at the Bureau des Longitudes, Paris.

- **Source Reference**: Jean Meeus, *Astronomical Algorithms* (2nd ed.), Chapter 32 (Planetary Positions) and Chapter 25 (Solar Coordinates).
- **Implementation**: The engine uses the most significant periodic terms (typically those > 0.0001°) to balance extreme calculation speed in pure PHP with sufficient accuracy for Panchang boundaries.
- **Precision**: Better than 0.01° for the years 1900–2100.

### The Moon

Lunar positions use a truncated version of the **ELP2000-82** theory by Chapront-Touzé and Chapront.

- **Source Reference**: Jean Meeus, *Astronomical Algorithms* (2nd ed.), Chapter 47 (Position of the Moon).
- **Implementation**: Computes 60 periodic terms for longitude and 60 for latitude, alongside planetary perturbation corrections (Venus/Jupiter).
- **Precision**: Longitude error typically ≤ 10 arcseconds (0.003°), latitude ≤ 30 arcseconds (0.008°).

### Lunar Nodes (Rahu/Ketu)

Mean lunar nodes derived from the Ω (Omega) term in the lunar argument calculations, used for nutation.

- **Implementation**: Mean ascending node (Rahu) = Ω; Mean descending node (Ketu) = Ω + 180°.
- **Note**: Mean nodes are standard practice in Vedic astrology; true nodes differ by < 1°.

## Coordinate Systems and Corrections

### Nutation & Obliquity

Nutation in longitude (Δψ) and obliquity (Δε) computed using the **IAU 1980 Theory of Nutation**.

- **Source Reference**: Jean Meeus, *Astronomical Algorithms* (2nd ed.), Chapter 22.
- **Implementation**: 63-term truncated series.
- **Precision**: ~0.5 arcseconds.

### Sidereal Time

Mean Sidereal Time calculated using the standard IAU polynomial for J2000.0. Local Mean Sidereal Time (LMST) derived by adding observer's geographic longitude.

- **Source Reference**: Jean Meeus, *Astronomical Algorithms* (2nd ed.), Chapter 12.

### Ayanamsha (Precession of the Equinoxes)

Panchang limbs rely on sidereal (Nirayana) coordinates, requiring subtraction of Ayanamsha from tropical (Sayana) longitudes.

| System | Description | Reference |
|--------|-------------|-----------|
| **Lahiri (Chitra Paksha)** | Standard Indian government ayanamsha. Fixed 0° near 285 AD, aligned with Spica. | Default |
| **Raman** | Proposed by B.V. Raman. | Alternative |
| **Krishnamurti (KP)** | Used in KP Astrology. | Alternative |

- **Implementation**: Standard precession polynomials. True Ayanamsha = Mean Ayanamsha + Nutation in longitude (Δψ).

## Event Discovery (Root Finding)

Panchang boundaries (Tithi End, Nakshatra End, etc.) occur when Sun and Moon reach specific relative angles. Finding exact times requires solving for roots of continuous astronomical functions.

### Adaptive Secant Interpolation

The engine uses **Secant/Brent's Method** combined with adaptive time-stepping:

1. **Fractional Jump**: Instead of fixed intervals (e.g., 30 minutes), the engine determines fractional progress of the current limb (e.g., 0.8/1.0) and dynamically jumps time forward near the boundary.
2. **Root Refinement**: Secant method interpolates the remaining fraction, rapidly converging on the exact second.
3. **Tolerance**: Convergence tolerance strictly set to **5 seconds** of astronomical time.

### Solar Events (Sunrise/Sunset)

- **Algorithm**: Iterative refinement using topocentric apparent solar altitude.
- **Horizon**: Standard altitude h₀ = -0.8333° (includes refraction + solar disc).
- **Dip Correction**: 1.92 × √(elevation in meters) arcminutes for observer elevation.
- **Polar Regions**: Returns `null` when sun doesn't rise/set (latitude > 66.5°).

### Lunar Events (Moonrise/Moonset)

- Similar iterative method using topocentric apparent lunar altitude.
- Includes parallax correction (major for Moon).
- Returns `null` on days without rise/set.

## Calendar Calculations

### Lunar Month Determination

- **Amanta** (default): Month ends at Amavasya (New Moon).
- **Purnimanta**: Month ends at Purnima (Full Moon).
- **Adhika Masa** (Intercalary): Detected when two New Moons occur in one solar month.
- **Kshaya Masa** (Omitted): Rare; detected when no New Moon in a solar month.

### Samvat Calculations

- **Vikram Samvat**: Lunar year starting Chaitra Shukla Pratipada (approx. 57 years ahead of Gregorian).
- **Shaka Samvat**: Solar year starting at Mesha Sankranti (approx. 78 years behind Gregorian).

### Solar Month (Sidereal)

Determined by Sun's sidereal longitude (12 signs of 30° each).

## Panchanga Limb Calculations

| Limb | Formula | Cycle |
|------|---------|-------|
| **Tithi** | floor((MoonLon - SunLon) / 12°) mod 30 | 29.53 days |
| **Nakshatra** | floor(MoonLon / 13°20') mod 27 | 27.32 days |
| **Yoga** | floor((SunLon + MoonLon) / 13°20') mod 27 | 27.32 days |
| **Karana** | floor((MoonLon - SunLon) / 6°) mod 60 → mapped to 11 karanas | 14.77 days |
| **Vara** | Weekday at sunrise (ISO weekday) | 7 days |

All longitudes are **sidereal** (tropical - ayanamsha).

## Implementation Architecture

### Pure PHP Engine (`PurePhpAstronomyEngine`)

- **No extensions**: Pure PHP 8.2+ math functions only.
- **Caching**: In-memory memoization per (body, JD_TT) + optional PSR-16 persistent cache.
- **Coordinate Transforms**: Ecliptic ↔ Equatorial using rotation matrices.
- **Time Scale**: TT (Terrestrial Time) = UT + ΔT; ΔT from Espenak/Meeus polynomials.

### Key Classes

| Class | Responsibility |
|-------|----------------|
| `SolarPositionCalculator` | Sun position (VSOP87) |
| `LunarPositionCalculator` | Moon position (ELP2000) |
| `PlanetPositionCalculator` | Mercury-Saturn (VSOP87 terms) |
| `NutationCalculator` | IAU 1980 nutation |
| `ObliquityCalculator` | Mean + true obliquity |
| `SiderealTimeCalculator` | GMST, LMST |
| `Ayanamsha` implementations | Lahiri, Raman, KP |
| `TransitionFinder` | Root-finding for limb boundaries |
| `PanchangaCalculator` | Orchestrates all limb calculations |

## Limitations & Disclosures

1. **Delta T**: Calculations before 1600 AD or after 2200 AD rely on extrapolated ΔT polynomials. Earth's rotation slows unpredictably; historical/future times may drift.

2. **Extreme Latitudes**: Beyond polar circles (> 66.5°), Sun may not rise/set for extended periods. `sunrise`/`sunset` return `null`.

3. **Truncated Series**: Unlike libraries wrapping SWISSEPH (Swiss Ephemeris), Vittix uses truncated VSOP87/ELP2000 for sub-second performance in pure PHP.

4. **Date Range**: Officially supported and tested: **1900–2100 AD**. Outside this range, precision degrades exponentially.

5. **No Relativistic Corrections**: Light-time, aberration, and gravitational deflection not modeled (sub-arcsecond effects).

6. **Mean Nodes Only**: Rahu/Ketu are mean nodes; true nodes not computed.

7. **Whole-Sign Houses Only**: Bhava Chalit (unequal houses) not implemented.

8. **Vimshottari Dasha Only**: Other dasha systems (Yogini, Kalachakra, etc.) not implemented.