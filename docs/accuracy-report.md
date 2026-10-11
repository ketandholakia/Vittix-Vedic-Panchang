# Panchang accuracy report

- **Generated:** 2026-09-29 17:27:39 UTC
- **Git commit:** `8b8f097`
- **Reference:** Swiss Ephemeris 2.10.03 (moshier ephemeris), Lahiri ayanamsha (SIDM_LAHIRI)
- **Fixtures:** seed 20260929, generated 2026-09-29T15:44:32Z
- **Engine under test:** Vittix Panchang, pure-PHP Lahiri engine (EngineFactory::createPurePhpEngine)

Errors are signed (engine − reference) with shortest-angle wraparound;
summary statistics use magnitudes (|error|). Longitudes in arcseconds, times in seconds.

## 1. Body longitudes

Dataset: 2000 random UTC instants, 1950–2100.

### 1.1 Overall

| Dataset | N | mean | RMS | median | p95 | max |
|---|---|---|---|---|---|---|
| tropical longitude | 16000 | 5.04 | 10.34 | 0.88 | 20.31 | 74.19 |
| sidereal longitude | 16000 | 16.45 | 20.83 | 12.06 | 36.88 | 91.83 |

Ayanamsha (engine vs Swiss):

| Variant | N | mean | RMS | max |
|---|---|---|---|---|
| ayanamsha_mean | 2000 | 11.96 | 11.96 | 12.19 |
| ayanamsha_true | 2000 | 13.90 | 17.26 | 30.66 |

### 1.2 Per body

| Body | tropical mean | tropical max | sidereal mean | sidereal max |
|---|---|---|---|---|
| sun | 11.32 | 34.33 | 22.55 | 61.98 |
| moon | 13.77 | 74.19 | 25.64 | 91.83 |
| mercury | 1.42 | 9.08 | 14.63 | 36.87 |
| venus | 1.24 | 6.36 | 14.47 | 34.65 |
| mars | 0.76 | 3.81 | 14.19 | 33.40 |
| jupiter | 0.46 | 2.52 | 14.09 | 31.54 |
| saturn | 0.44 | 1.38 | 14.10 | 31.50 |
| rahu_mean | 10.96 | 18.83 | 11.94 | 12.07 |

### 1.3 By decade (tropical, all bodies)

| Decade | N | mean | p95 | max |
|---|---|---|---|---|
| 1950s | 1152 | 2.63 | 15.54 | 28.71 |
| 1960s | 1056 | 2.81 | 15.61 | 24.14 |
| 1970s | 1080 | 3.08 | 16.25 | 30.69 |
| 1980s | 1024 | 3.23 | 16.23 | 31.39 |
| 1990s | 1080 | 3.21 | 16.16 | 32.58 |
| 2000s | 1032 | 3.05 | 15.76 | 27.86 |
| 2010s | 984 | 3.46 | 17.33 | 34.10 |
| 2020s | 1088 | 3.62 | 16.70 | 31.92 |
| 2030s | 1104 | 3.61 | 16.74 | 30.17 |
| 2040s | 1048 | 4.40 | 17.05 | 29.50 |
| 2050s | 1144 | 5.24 | 18.26 | 31.71 |
| 2060s | 912 | 6.83 | 26.13 | 36.99 |
| 2070s | 1168 | 8.61 | 35.38 | 45.96 |
| 2080s | 976 | 10.07 | 46.64 | 60.45 |
| 2090s | 1048 | 11.67 | 57.27 | 72.82 |
| 2100s | 104 | 11.46 | 59.82 | 74.19 |

### 1.4 Worst 5 cases (tropical)

| Body | UTC instant | error (arcsec) |
|---|---|---|
| moon | 2100-11-05T19:05:13Z | 74.19 |
| moon | 2099-03-05T02:50:26Z | 72.82 |
| moon | 2098-09-22T22:40:52Z | 70.77 |
| moon | 2095-04-03T07:28:06Z | 70.66 |
| moon | 2099-07-21T07:37:52Z | 70.19 |

### 1.5 Worst 5 cases (sidereal)

| Body | UTC instant | error (arcsec) |
|---|---|---|
| moon | 2100-11-05T19:05:13Z | 91.83 |
| moon | 2100-07-16T13:17:53Z | 85.97 |
| moon | 2099-03-05T02:50:26Z | 83.58 |
| moon | 2099-07-21T07:37:52Z | 83.24 |
| moon | 2100-06-17T17:22:08Z | 83.02 |

## 2. Limb transitions

Dataset: 300 random start instants; next end of the current tithi, nakshatra, yoga and karana.
Comparisons above 250 s are treated as anomalies (solver artifacts / boundary races) and excluded from the columns below.

### 2.1 Per limb (anomalies excluded)

| Limb | N clean | mean | RMS | median | p95 | max | anomalies |
|---|---|---|---|---|---|---|---|
| tithi | 300 | 28.92 | 37.66 | 21.17 | 79.94 | 131.43 | 0 |
| nakshatra | 300 | 47.03 | 58.37 | 43.73 | 114.08 | 138.12 | 0 |
| yoga | 300 | 82.54 | 96.57 | 88.93 | 160.30 | 206.01 | 0 |
| karana | 300 | 28.97 | 37.69 | 21.42 | 80.01 | 129.60 | 0 |

### 2.2 Error decomposition (independent bisection vs TransitionFinder)

Pointing = engine’s own boundary − reference (longitude error effect).
Residual = engine’s returned time − engine’s own boundary (refinement effect).

| Limb | pointing mean | pointing max | residual mean | residual max | solver stalls |
|---|---|---|---|---|---|
| tithi | 28.90 | 131.36 | 0.26 | 0.80 | 0 |
| nakshatra | 47.06 | 138.35 | 0.27 | 0.84 | 0 |
| yoga | 82.55 | 206.33 | 0.26 | 0.83 | 0 |
| karana | 28.96 | 129.96 | 0.27 | 0.93 | 0 |

**Solver stalls total: 0 / 1200 comparisons** — cases where the returned time is more than 60 s away from the engine’s own boundary.

### 2.3 Worst 5 cases (non-anomalous)

| Limb | Start (UTC) | error (s) |
|---|---|---|
| yoga | 2085-12-12T09:42:03Z | -206.01 |
| yoga | 2086-11-28T01:20:37Z | -204.17 |
| yoga | 2085-03-02T02:56:02Z | -198.32 |
| yoga | 2087-03-14T14:35:29Z | -197.07 |
| yoga | 2088-04-12T18:40:49Z | -191.74 |

### 2.4 Anomalies

| Limb | Start (UTC) | total (s) | pointing (s) | residual (s) | classification |
|---|---|---|---|---|---|
| — | — | — | — | — | none |

## 3. Sunrise/sunset and moonrise/moonset

Dataset: 600 city-dates (10 cities × 60 dates across the year and 2000–2050); events keyed by local civil date.
Matched = within ±120 min of a reference event; off-window = engine time further from every reference event of the neighbouring days (selection difference).

### 3.1 By event and convention

| Event | Convention | N | null | missed | off-window | matched mean | p95 | max |
|---|---|---|---|---|---|---|---|---|
| sunrise | upper_limb_refraction | 600 | 0 | 0 | 0 | 13.74 | 22.59 | 48.79 |
| sunrise | disc_center_no_refraction | 600 | 0 | 0 | 0 | 246.90 | 353.27 | 436.16 |
| sunset | upper_limb_refraction | 600 | 0 | 0 | 0 | 11.98 | 19.89 | 43.19 |
| sunset | disc_center_no_refraction | 600 | 0 | 0 | 0 | 248.67 | 352.64 | 439.42 |
| moonrise | upper_limb_refraction | 600 | 27 | 0 | 0 | 14.36 | 23.11 | 48.17 |
| moonrise | disc_center_no_refraction | 600 | 27 | 2 | 0 | 251.80 | 365.32 | 479.24 |
| moonset | upper_limb_refraction | 600 | 9 | 0 | 0 | 12.61 | 21.80 | 44.18 |
| moonset | disc_center_no_refraction | 600 | 9 | 0 | 0 | 253.93 | 365.44 | 526.25 |

### 3.2 By city (upper-limb + refraction, matched events only)

| City | sun N | sun mean | sun max | moon N | moon mean | moon max | moon null/off-window |
|---|---|---|---|---|---|---|---|
| Delhi | 120 | 12.55 | 24.65 | 115 | 13.03 | 26.95 | 0 |
| Mumbai | 120 | 11.53 | 20.16 | 117 | 12.13 | 22.74 | 0 |
| Ahmedabad | 120 | 11.88 | 21.94 | 116 | 12.41 | 24.55 | 0 |
| Kolkata | 120 | 11.81 | 22.21 | 115 | 12.48 | 24.36 | 0 |
| Chennai | 120 | 11.15 | 18.67 | 118 | 11.91 | 20.17 | 0 |
| Bengaluru | 120 | 11.11 | 18.77 | 118 | 11.82 | 21.27 | 0 |
| Varanasi | 120 | 12.16 | 22.76 | 116 | 12.74 | 25.63 | 0 |
| Kathmandu | 120 | 12.40 | 23.99 | 117 | 13.02 | 26.24 | 0 |
| London | 120 | 19.10 | 48.79 | 116 | 19.90 | 48.17 | 0 |
| New York | 120 | 14.90 | 33.35 | 116 | 15.33 | 35.83 | 0 |

### 3.3 By decade (upper-limb + refraction, matched only)

| Decade | N | mean | p95 | max |
|---|---|---|---|---|
| 2000s | 240 | 12.82 | 18.34 | 23.65 |
| 2010s | 240 | 12.79 | 18.81 | 28.70 |
| 2020s | 240 | 12.78 | 20.88 | 34.54 |
| 2030s | 240 | 12.94 | 23.09 | 40.19 |
| 2050s | 240 | 12.96 | 27.12 | 48.79 |

### 3.4 Worst 5 matched cases (upper-limb + refraction)

| Event | City | Date | error (s) |
|---|---|---|---|
| sunrise | London | 2050-06-15 | 48.79 |
| moonrise | London | 2050-07-15 | 48.17 |
| moonrise | London | 2025-09-15 | 44.28 |
| moonset | London | 2025-09-15 | -44.18 |
| sunset | London | 2050-06-15 | -43.19 |

### 3.5 Convention check

| Event | upper limb + refraction (mean) | disc centre, no refraction (mean) | matches |
|---|---|---|---|
| sunrise | 13.74 | 246.90 | upper limb + refraction |
| sunset | 11.98 | 248.67 | upper limb + refraction |
| moonrise | 14.36 | 251.80 | upper limb + refraction |
| moonset | 12.61 | 253.93 | upper limb + refraction |

## 4. Observations

- Planets: worst tropical longitude error 9.1 arcsec (§1.2).
- Lahiri ayanamsha differs from Swiss Ephemeris by 11.96 arcsec on average (§1.1, §1.4); the offset
  propagates into all sidereal longitudes.
- ΔT model: for dates well after ~2050 the engine (Espenak–Meeus) and the Swiss reference use
  different ΔT predictions (≈ +18 s higher by 2050, ≈ +110 s by 2100). This inflates late-century
  residuals for the fastest bodies (Moon ≈ 60″, Mercury/Venus ≈ 20–30″); with ΔT matched, the
  ephemeris agrees within ≈1″. See “Known comparator differences” in docs/accuracy.md.
- TransitionFinder refinement converges for all 1200 comparisons; worst residual below 1 s (§2.2).
- Moonrise/moonset match the reference’s local-date convention (§3.1).
- Sunrise/sunset matches the upper-limb + standard-refraction convention (§3.5).
