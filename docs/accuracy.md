# Accuracy and Supported Date Range

Vittix Panchang uses a highly optimized pure-PHP astronomical engine designed for standard astrological and calendrical computations.

## Supported Date Range

**The officially supported and tested date range is 1900–2100 AD.**

Calculations outside this range will still execute without throwing errors, but the precision degrades exponentially.

### Why 1900–2100?

1. **Delta T Approximations**: The time difference between Terrestrial Time (TT) and Universal Time (UT) is known as Delta T (ΔT). This engine uses the standard polynomial approximations from Espenak and Meeus for the 20th and 21st centuries. For dates outside this range (especially historical dates before 1600 AD), these polynomials diverge from historical eclipse records.

2. **Orbital Perturbations**: A high-precision engine (like VSOP87 or JPL DE431) evaluates thousands of periodic terms for planetary orbits. For extreme speed in pure PHP, this engine evaluates a truncated series of periodic terms (usually those > 0.0001 degrees) and assumes linear/quadratic changes to orbital elements. These truncated terms are highly accurate near J2000.0 but drift over millennia.

3. **Nutation & Precession**: The IAU nutation models and precession matrices used here are linearized around J2000.0.

## Expected Precision (1900–2100)

Within the supported range, the engine provides the following precision compared to JPL DE-based Swiss Ephemeris:

| Calculation | Typical Error | Max Error | Implication for Panchang |
|-------------|---------------|-----------|--------------------------|
| Sun Longitude | < 0.0003° (1") | 0.0008° | Negligible (~2 seconds drift in Sankranti) |
| Moon Longitude | < 0.002° (7") | 0.005° (18") | Minor (~10 seconds drift in Tithi boundaries) |
| Lunar Node (Rahu/Ketu) | < 0.001° | 0.002° | Negligible |
| Planets (Mercury-Saturn) | < 0.005° | 0.02° | Negligible for house placements, slight drift in exact aspect timings |
| Ayanamsha (Lahiri/Raman/KP) | Exact | Exact | Matches standard polynomial definitions precisely |
| Ascendant (Lagna) | < 0.01° | 0.02° | < 30 seconds in Lagna degree |
| Sunrise/Sunset | < 10 seconds | 30 seconds | Includes refraction & dip correction |

### Near-Boundary Warnings

Panchang limbs (Tithi, Nakshatra, etc.) are discrete periods. Because there is a very small computational variance (e.g. ±10 seconds for a Tithi end time), if a limb ends exactly at sunrise (within a few seconds), the engine might flip the *active* limb for that day.

The engine detects this automatically and sets the `accuracy->nearBoundary` flag to `true` when a transition happens within **2 minutes of sunrise**, warning you that the calculated result is sensitive to minute computational differences or location coordinate precision.

### Horizon Dip and Elevation

Sunrise and Sunset calculations incorporate standard atmospheric refraction and horizon dip based on observer elevation.

- Standard altitude `h₀ = -0.8333°` (refraction 34' + solar semi-diameter 16')
- Dip correction `= 1.92 × √(elevation in meters)` arcminutes

This ensures accuracy for mountainous locations where sunrise occurs earlier than at sea level.

## AccuracyInfo Object

Every `PanchangDay` and `PanchangResult` includes an `AccuracyInfo` object:

```php
$day->accuracy->nearBoundary;                    // bool
$day->accuracy->estimatedAngularErrorDegrees;    // float
$day->accuracy->estimatedTimeErrorSeconds;       // float
```

| Property | Description |
|----------|-------------|
| `nearBoundary` | `true` if any limb transition within 2 minutes of sunrise |
| `estimatedAngularErrorDegrees` | Estimated max positional error in degrees |
| `estimatedTimeErrorSeconds` | Estimated max timing error for limb boundaries |

## Architecture & Accuracy Features

- **Planetary positions**: Truncated VSOP87 series (Sun, Mercury-Saturn), ELP2000-82 (Moon)
- **Nutation**: IAU 1980 63-term series
- **Obliquity**: Mean + true obliquity with nutation
- **Sidereal time**: IAU 2000/2006 polynomials (J2000.0)
- **Ayanamsha**: Configurable (Lahiri, Raman, Krishnamurti) with true ayanamsha = mean + Δψ
- **Ascendant**: Meeus formulas with nutation/obliquity
- **Lunar nodes**: Mean nodes from Ω term (standard for Vedic)
- **Root finding**: Adaptive secant/Brent method, 5-second tolerance
- **Time scale**: TT = UT + ΔT (Espenak/Meeus polynomials)

## Comparison with Other Engines

| Feature | Vittix Panchang | Swiss Ephemeris (sweph) | JPL DE431 |
|---------|-----------------|-------------------------|-----------|
| Language | Pure PHP 8.2+ | C library + PHP ext | C/Fortran |
| Extensions | None required | `sweph` PHP extension | Native |
| Speed | ~5-10ms (warm) | ~1-5ms | ~10-50ms |
| Precision (1900-2100) | < 0.01° | < 0.001° | < 0.0001° |
| Date range | 1900-2100 (official) | 3000 BC - 3000 AD | 1550-2650 AD |
| Dependencies | PSR-16 cache (opt) | Swiss Ephemeris files | JPL kernels |

## When to Use What

**Use Vittix Panchang when:**
- Building PHP/Laravel applications
- Need pure PHP with no extensions
- Date range 1900-2100
- Sub-arcminute accuracy sufficient
- Want zero-config deployment

**Consider Swiss Ephemeris when:**
- Need arcsecond precision
- Historical dates before 1600 AD
- Already have C extension capability
- Building high-precision professional software

## Verification

The test suite includes regression snapshots against known reference data. Run tests to verify accuracy:

```bash
composer test
```

Key test categories:
- Planetary position regression tests
- Panchang limb boundary tests
- Sunrise/sunset accuracy tests
- Ayanamsha verification tests
- Festival date verification tests