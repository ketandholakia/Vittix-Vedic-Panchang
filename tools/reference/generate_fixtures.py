#!/usr/bin/env python3
"""
Swiss Ephemeris reference-fixture generator for the Vittix Panchang accuracy harness.

Generates three deterministic datasets under tests/fixtures/reference/:

  * body_longitudes.json  — 2000 random UTC instants (1950-01-01 .. 2100-12-31).
                            For Sun, Moon, Mercury .. Saturn and mean Rahu:
                            tropical apparent longitude, Lahiri sidereal longitude,
                            plus the ayanamsha values themselves.
  * transitions.json      — 300 random start instants; for each, the END time (UTC,
                            sub-second via root finding) of the tithi, nakshatra,
                            yoga and karana current at the start instant.
  * sun_moon_events.json  — sunrise, sunset, moonrise, moonset (UTC) for ten cities
                            on 60 dates spread across the year and 2000-2050, in TWO
                            rise/set conventions: (i) upper limb + standard refraction
                            and (ii) disc centre with no refraction.

Determinism: every random choice comes from a built-in 64-bit LCG seeded with a
fixed constant (see SEED). The script does not depend on Python's `random`
module, so output is reproducible across Python versions given the same
pyswisseph version.

Definitions used for the transition dataset follow the Panchang engine:

  * tithi     : floor(normalize(moon_tropical - sun_tropical) / 12)     -> 0..29
  * karana    : floor(normalize(moon_tropical - sun_tropical) / 6)      -> 0..59
                (index i corresponds to the i-th half-tithi; naming per the
                standard karana scheme, 0 = first half of Shukla Pratipada)
  * nakshatra : floor(normalize(moon_sidereal) / (360/27))              -> 0..26
  * yoga      : floor(normalize(sun_sidereal + moon_sidereal) / (360/27)) -> 0..26

Tropical longitudes are apparent (light-time/aberration and nutation applied,
true equinox of date) as returned by swe.calc_ut() without SEFLG_SIDEREAL.
Sidereal longitudes use swe.set_sid_mode(swe.SIDM_LAHIRI) + SEFLG_SIDEREAL.

Ephemeris: Moshier by default (no data files needed). Pass --ephemeris swiss to
use the Swiss Ephemeris data files (place them in --ephe-path or set the
SWEPH_PATH environment variable); the script aborts if the files are not found
instead of silently falling back to Moshier.

Requires: Python 3.8+ and pyswisseph (pip install -r tools/reference/requirements-dev.txt).

Usage:
    python tools/reference/generate_fixtures.py                 # full set, Moshier
    python tools/reference/generate_fixtures.py --quick         # tiny set for testing
    python tools/reference/generate_fixtures.py --ephemeris swiss --ephe-path /path/to/ephe
"""

from __future__ import annotations

import argparse
import datetime as dt
import json
import math
import os
import platform
import sys
from typing import Callable, Dict, List, Optional, Tuple

try:
    import swisseph as swe
except ImportError:  # pragma: no cover - environment guard
    sys.exit(
        "pyswisseph is required. Install it first:\n"
        "    pip install -r tools/reference/requirements-dev.txt"
    )

# --------------------------------------------------------------------------- #
# Constants
# --------------------------------------------------------------------------- #

SEED = 20260929
GREG_CAL = swe.GREG_CAL
UTC = dt.timezone.utc

# Seed actually used for the current run (updated via _set_seed() so that
# metadata does not depend on mutable local state inside main()).
_SEED_USED = SEED


def _set_seed(seed: int) -> None:
    global _SEED_USED
    _SEED_USED = seed

# Bodies recorded in body_longitudes.json (name -> swisseph identifier).
BODY_IDS: List[Tuple[str, int]] = [
    ("sun", swe.SUN),
    ("moon", swe.MOON),
    ("mercury", swe.MERCURY),
    ("venus", swe.VENUS),
    ("mars", swe.MARS),
    ("jupiter", swe.JUPITER),
    ("saturn", swe.SATURN),
    ("rahu_mean", swe.MEAN_NODE),
]

# Limb definitions for transitions.json: (name, angle source, segment degrees).
TITHI_SEGMENT = 12.0
KARANA_SEGMENT = 6.0
NAKSHATRA_SEGMENT = 360.0 / 27.0

LIMB_KEYS = ("tithi", "nakshatra", "yoga", "karana")

# Cities for sun_moon_events.json.
CITIES: List[Dict[str, object]] = [
    {"name": "Delhi", "latitude": 28.6139, "longitude": 77.2090, "timezone": "Asia/Kolkata"},
    {"name": "Mumbai", "latitude": 19.0760, "longitude": 72.8777, "timezone": "Asia/Kolkata"},
    {"name": "Ahmedabad", "latitude": 23.0225, "longitude": 72.5714, "timezone": "Asia/Kolkata"},
    {"name": "Kolkata", "latitude": 22.5726, "longitude": 88.3639, "timezone": "Asia/Kolkata"},
    {"name": "Chennai", "latitude": 13.0827, "longitude": 80.2707, "timezone": "Asia/Kolkata"},
    {"name": "Bengaluru", "latitude": 12.9716, "longitude": 77.5946, "timezone": "Asia/Kolkata"},
    {"name": "Varanasi", "latitude": 25.3176, "longitude": 82.9739, "timezone": "Asia/Kolkata"},
    {"name": "Kathmandu", "latitude": 27.7172, "longitude": 85.3240, "timezone": "Asia/Kathmandu"},
    {"name": "London", "latitude": 51.5074, "longitude": -0.1278, "timezone": "Europe/London"},
    {"name": "New York", "latitude": 40.7128, "longitude": -74.0060, "timezone": "America/New_York"},
]

EVENT_YEARS = [2000, 2012, 2025, 2037, 2050]
EVENT_MONTHS = list(range(1, 13))
EVENT_DAY = 15

EVENT_TYPES = ("sunrise", "sunset", "moonrise", "moonset")

# Rise/set conventions: label -> rsmi extra bits.
CONVENTION_BITS: Dict[str, int] = {
    "upper_limb_refraction": 0,
    "disc_center_no_refraction": swe.BIT_DISC_CENTER | swe.BIT_NO_REFRACTION,
}


class GenerationError(RuntimeError):
    """Raised when the reference data cannot be produced as specified."""


# --------------------------------------------------------------------------- #
# Deterministic RNG (64-bit LCG; independent of Python's random module)
# --------------------------------------------------------------------------- #


class Lcg:
    """Deterministic 64-bit linear congruential generator (Knuth's MMIX)."""

    _MULT = 6364136223846793005
    _INC = 1442695040888963407
    _MASK = (1 << 64) - 1

    def __init__(self, seed: int) -> None:
        self._state = seed & Lcg._MASK

    def next_u64(self) -> int:
        self._state = (self._state * Lcg._MULT + Lcg._INC) & Lcg._MASK
        return self._state

    def randint(self, lo: int, hi_inclusive: int) -> int:
        """Uniform integer in [lo, hi_inclusive]."""
        span = hi_inclusive - lo + 1
        if span <= 0:
            raise ValueError("empty range")
        return lo + (self.next_u64() % span)


# --------------------------------------------------------------------------- #
# Small astronomy helpers
# --------------------------------------------------------------------------- #


def wrap360(deg: float) -> float:
    return deg % 360.0


def jd_ut_from_civil(year: int, month: int, day: int, second_of_day: int) -> float:
    """JD (UT) for a civil date + integer second-of-day. Exact within float64."""
    hours = second_of_day / 3600.0
    return swe.julday(year, month, day, hours, GREG_CAL)


def jd_to_utc(jd: float) -> dt.datetime:
    """Convert a JD (UT) to an aware UTC datetime (sub-second preserved)."""
    year, month, day, hour = swe.revjul(jd, GREG_CAL)
    base = dt.datetime(year, month, day, tzinfo=UTC)
    return base + dt.timedelta(hours=hour)


def utc_to_iso(moment: dt.datetime, millis: bool = False) -> str:
    moment = moment.astimezone(UTC)
    if millis:
        text = moment.strftime("%Y-%m-%dT%H:%M:%S.") + f"{moment.microsecond // 1000:03d}Z"
        return text
    return moment.strftime("%Y-%m-%dT%H:%M:%SZ")


def round8(value: float) -> float:
    return round(value, 8)


# --------------------------------------------------------------------------- #
# Ephemeris setup
# --------------------------------------------------------------------------- #


def setup_ephemeris(mode: str, ephe_path: Optional[str]) -> Tuple[int, str]:
    """Configure swisseph. Returns (calc flags, ephemeris label)."""
    swe.set_ephe_path(None)
    if mode == "swiss":
        path = ephe_path or os.environ.get("SWEPH_PATH") or "."
        swe.set_ephe_path(path)
        probe_jd = swe.julday(2024, 1, 1, 12.0, GREG_CAL)
        _result, retflags = swe.calc_ut(probe_jd, swe.SUN, swe.FLG_SWIEPH)
        used_swiss = bool(retflags & swe.FLG_SWIEPH)
        if not used_swiss:
            raise GenerationError(
                f"Swiss ephemeris files were not found in '{path}'. "
                "Place sepl_18.se1 / semo_18.se1 / seas_18.se1 etc. there, "
                "pass --ephe-path, or set SWEPH_PATH. Refusing to silently "
                "fall back to Moshier."
            )
        return swe.FLG_SWIEPH, "swiss"
    return swe.FLG_MOSEPH, "moshier"


def calc_lon(jd_ut: float, body: int, flags: int) -> float:
    """Apparent ecliptic longitude in degrees [0, 360)."""
    xx, _retflags = swe.calc_ut(jd_ut, body, flags)
    return xx[0]


def ayanamsha_values(jd_ut: float, flags: int) -> Tuple[float, float]:
    """(mean, true) Lahiri ayanamsha in degrees.

    * mean — swe.get_ayanamsa_ut(): the geometric ayanamsha without nutation.
    * true — swe.get_ayanamsa_ex_ut(): the value SEFLG_SIDEREAL subtracts from
             apparent tropical longitudes.
    """
    mean_ay = swe.get_ayanamsa_ut(jd_ut)
    true_ay = swe.get_ayanamsa_ex_ut(jd_ut, flags)[1]
    return mean_ay, true_ay


# --------------------------------------------------------------------------- #
# Dataset A: body longitudes
# --------------------------------------------------------------------------- #


def generate_body_longitudes(count: int, flags: int, eph_label: str, rng: Lcg) -> Dict[str, object]:
    jd_lo = swe.julday(1950, 1, 1, 0.0, GREG_CAL)
    jd_hi = swe.julday(2100, 12, 31, 23.0, GREG_CAL) + (59 * 60 + 59) / 86400.0
    total_seconds = int(round((jd_hi - jd_lo) * 86400.0))

    samples: List[Dict[str, object]] = []
    for _ in range(count):
        second = rng.randint(0, total_seconds - 1)
        jd = jd_lo + second / 86400.0
        mean_ay, true_ay = ayanamsha_values(jd, flags)
        bodies: Dict[str, Dict[str, float]] = {}
        for name, body_id in BODY_IDS:
            tropical = calc_lon(jd, body_id, flags)
            sidereal = calc_lon(jd, body_id, flags | swe.FLG_SIDEREAL)
            bodies[name] = {"tropical": round8(tropical), "sidereal": round8(sidereal)}
        samples.append(
            {
                "jd_ut": round(jd, 9),
                "utc": utc_to_iso(jd_to_utc(jd)),
                "ayanamsha_mean": round8(mean_ay),
                "ayanamsha_true": round8(true_ay),
                "bodies": bodies,
            }
        )
        if len(samples) % 500 == 0:
            print(f"  body_longitudes: {len(samples)}/{count}", flush=True)

    return {
        "metadata": _metadata(
            fixture="body_longitudes",
            count=count,
            eph_label=eph_label,
            notes=(
                "Random UTC instants in [1950-01-01, 2100-12-31]. tropical = "
                "apparent geocentric ecliptic longitude (true equinox of date, "
                "swe.calc_ut default). sidereal = SEFLG_SIDEREAL with SIDM_LAHIRI. "
                "ayanamsha_mean = swe.get_ayanamsa_ut(); ayanamsha_true = "
                "swe.get_ayanamsa_ex_ut() (the value SEFLG_SIDEREAL applies)."
            ),
        ),
        "samples": samples,
    }


# --------------------------------------------------------------------------- #
# Dataset B: limb transitions
# --------------------------------------------------------------------------- #


class AngleEvaluator:
    """Angle functions for the four limbs with a small per-sample memo."""

    def __init__(self, flags: int) -> None:
        self._flags = flags
        self._cache: Dict[float, Tuple[float, float, float, float]] = {}

    def _quad(self, jd: float) -> Tuple[float, float, float, float]:
        cached = self._cache.get(jd)
        if cached is not None:
            return cached
        sun_trop = calc_lon(jd, swe.SUN, self._flags)
        moon_trop = calc_lon(jd, swe.MOON, self._flags)
        sun_sid = calc_lon(jd, swe.SUN, self._flags | swe.FLG_SIDEREAL)
        moon_sid = calc_lon(jd, swe.MOON, self._flags | swe.FLG_SIDEREAL)
        quad = (sun_trop, moon_trop, sun_sid, moon_sid)
        if len(self._cache) > 4096:
            self._cache.clear()
        self._cache[jd] = quad
        return quad

    def tithi_angle(self, jd: float) -> float:
        sun, moon, _s, _m = self._quad(jd)
        return wrap360(moon - sun)

    def karana_angle(self, jd: float) -> float:
        return self.tithi_angle(jd)

    def nakshatra_angle(self, jd: float) -> float:
        _sn, _mn, _ss, moon_sid = self._quad(jd)
        return wrap360(moon_sid)

    def yoga_angle(self, jd: float) -> float:
        _sn, _mn, sun_sid, moon_sid = self._quad(jd)
        return wrap360(sun_sid + moon_sid)


# Conservative maximum daily rates (°/day) used to size adaptive scan jumps.
_MAX_RATE = 17.5


def next_transition(
    jd0: float,
    angle_fn: Callable[[float], float],
    segment: float,
    max_days: float = 3.0,
) -> Tuple[float, int]:
    """First instant strictly after jd0 where floor(angle/segment) changes.

    Returns (jd_ut of the boundary, index at jd0).
    """
    index0 = int(angle_fn(jd0) // segment)
    jd = jd0
    limit = jd0 + max_days

    while True:
        angle = angle_fn(jd)
        remaining = segment - (angle % segment)
        step = min(max(remaining / _MAX_RATE, 0.002), 0.5)
        jd_next = jd + step
        if jd_next > limit:
            raise GenerationError("no transition found within the search window")
        if int(angle_fn(jd_next) // segment) != index0:
            break
        jd = jd_next

    lo, hi = jd, jd_next
    for _ in range(80):
        if (hi - lo) * 86400.0 < 0.05:
            break
        mid = (lo + hi) / 2.0
        if int(angle_fn(mid) // segment) == index0:
            lo = mid
        else:
            hi = mid
    return hi, index0


def generate_transitions(count: int, flags: int, eph_label: str, rng: Lcg) -> Dict[str, object]:
    jd_lo = swe.julday(1950, 1, 1, 0.0, GREG_CAL)
    jd_hi = swe.julday(2100, 12, 31, 23.0, GREG_CAL) + (59 * 60 + 59) / 86400.0
    total_seconds = int(round((jd_hi - jd_lo) * 86400.0))

    samples: List[Dict[str, object]] = []
    for _ in range(count):
        second = rng.randint(0, total_seconds - 1)
        jd_start = jd_lo + second / 86400.0
        evaluator = AngleEvaluator(flags)

        limbs: Dict[str, object] = {}
        job_by_limb = (
            ("tithi", evaluator.tithi_angle, TITHI_SEGMENT),
            ("nakshatra", evaluator.nakshatra_angle, NAKSHATRA_SEGMENT),
            ("yoga", evaluator.yoga_angle, NAKSHATRA_SEGMENT),
            ("karana", evaluator.karana_angle, KARANA_SEGMENT),
        )
        for name, fn, segment in job_by_limb:
            end_jd, index0 = next_transition(jd_start, fn, segment)
            limbs[name] = {
                "index_at_start": index0,
                "end_jd_ut": round(end_jd, 9),
                "end_utc": utc_to_iso(jd_to_utc(end_jd), millis=True),
            }

        samples.append(
            {
                "start_jd_ut": round(jd_start, 9),
                "start_utc": utc_to_iso(jd_to_utc(jd_start)),
                "limbs": limbs,
            }
        )
        if len(samples) % 50 == 0:
            print(f"  transitions: {len(samples)}/{count}", flush=True)

    return {
        "metadata": _metadata(
            fixture="transitions",
            count=count,
            eph_label=eph_label,
            notes=(
                "For each random start instant, the next end time of the current "
                "limb, found by sub-second root finding on Swiss Ephemeris "
                "longitudes. Definitions: tithi = floor(wrap(moon_trop - sun_trop)/12); "
                "karana = floor(wrap(moon_trop - sun_trop)/6) (60 half-tithi indices); "
                "nakshatra = floor(wrap(moon_sidereal)/(360/27)); "
                "yoga = floor(wrap(sun_sidereal + moon_sidereal)/(360/27)). "
                "end_utc is the first instant at which the index has changed."
            ),
        ),
        "samples": samples,
    }


# --------------------------------------------------------------------------- #
# Dataset C: rise/set events
# --------------------------------------------------------------------------- #


def collect_events(
    jd_start: float,
    body: int,
    rsmi: int,
    geopos: Tuple[float, float, float],
    flags: int,
) -> List[float]:
    """Collect consecutive rise (or set) events starting at jd_start."""
    events: List[float] = []
    jd = jd_start
    for _ in range(7):
        res, tret = swe.rise_trans(jd, body, rsmi, geopos, 0.0, 0.0, flags)
        if res != 0:
            raise GenerationError(
                f"rise_trans returned {res} (circumpolar / not found) for body {body}"
            )
        event_jd = tret[0]
        events.append(event_jd)
        jd = event_jd + 0.5
    return events


def events_by_local_date(
    events: List[float],
    tz_name: str,
) -> Dict[str, float]:
    from zoneinfo import ZoneInfo

    tz = ZoneInfo(tz_name)
    result: Dict[str, float] = {}
    for event_jd in events:
        local_date = jd_to_utc(event_jd).astimezone(tz).date().isoformat()
        result[local_date] = event_jd
    return result


def generate_events(flags: int, eph_label: str, years: List[int]) -> Dict[str, object]:
    samples: List[Dict[str, object]] = []

    for city in CITIES:
        name = str(city["name"])
        lat = float(city["latitude"])
        lon = float(city["longitude"])
        tz_name = str(city["timezone"])
        geopos = (lon, lat, 0.0)

        for year in years:
            for month in EVENT_MONTHS:
                date_str = f"{year:04d}-{month:02d}-{EVENT_DAY:02d}"
                # Search from two days before D 00:00 UT to reliably cover
                # local dates D-1 .. D+1 in every timezone.
                search_start = swe.julday(year, month, EVENT_DAY, 0.0, GREG_CAL) - 2.0

                events: Dict[str, Dict[str, Optional[Dict[str, object]]]] = {}
                for event_type, body, base_rsmi in (
                    ("sunrise", swe.SUN, swe.CALC_RISE),
                    ("sunset", swe.SUN, swe.CALC_SET),
                    ("moonrise", swe.MOON, swe.CALC_RISE),
                    ("moonset", swe.MOON, swe.CALC_SET),
                ):
                    per_convention: Dict[str, Dict[str, float]] = {}
                    for label, extra_bits in CONVENTION_BITS.items():
                        raw = collect_events(
                            search_start, body, base_rsmi | extra_bits, geopos, flags
                        )
                        per_convention[label] = events_by_local_date(raw, tz_name)

                    # Compose D-1, D, D+1 records with both conventions.
                    day = dt.date(year, month, EVENT_DAY)
                    window: Dict[str, Optional[Dict[str, object]]] = {}
                    for offset in (-1, 0, 1):
                        key_date = (day + dt.timedelta(days=offset)).isoformat()
                        entry: Dict[str, Optional[Dict[str, object]]] = {}
                        for label in CONVENTION_BITS:
                            event_jd = per_convention[label].get(key_date)
                            if event_jd is None:
                                entry[label] = None
                            else:
                                entry[label] = {
                                    "local_date": key_date,
                                    "jd_ut": round(event_jd, 9),
                                    "utc": utc_to_iso(jd_to_utc(event_jd), millis=True),
                                }
                        window[key_date] = {
                            "offset": offset,
                            "conventions": entry,
                        }
                    events[event_type] = window

                samples.append(
                    {
                        "date": date_str,
                        "city": name,
                        "timezone": tz_name,
                        "latitude": lat,
                        "longitude": lon,
                        "elevation_m": 0.0,
                        "events": events,
                    }
                )

        print(f"  sun_moon_events: {name} done", flush=True)

    return {
        "metadata": _metadata(
            fixture="sun_moon_events",
            count=len(samples),
            eph_label=eph_label,
            notes=(
                "Rise/set events keyed by the LOCAL civil date of the event in the "
                "city's timezone. Each event has two entries: 'D-1', 'D', 'D+1' "
                "(relative to the requested date D), each carrying two conventions: "
                "'upper_limb_refraction' (swe.rise_trans default: upper limb, "
                "standard refraction) and 'disc_center_no_refraction' "
                "(BIT_DISC_CENTER | BIT_NO_REFRACTION). atpress/attemp left at "
                "swisseph defaults (1013.25 mbar, 15 C), observer elevation 0 m."
            ),
        ),
        "conventions": {
            "upper_limb_refraction": {
                "rsmi": "swe.CALC_RISE | swe.CALC_SET",
                "disc": "upper limb",
                "refraction": True,
                "elevation_m": 0.0,
            },
            "disc_center_no_refraction": {
                "rsmi": "swe.CALC_RISE | swe.CALC_SET | BIT_DISC_CENTER | BIT_NO_REFRACTION",
                "disc": "disc centre",
                "refraction": False,
                "elevation_m": 0.0,
            },
        },
        "samples": samples,
    }


# --------------------------------------------------------------------------- #
# Metadata
# --------------------------------------------------------------------------- #


def _metadata(fixture: str, count: int, eph_label: str, notes: str) -> Dict[str, object]:
    return {
        "fixture": fixture,
        "generator": "tools/reference/generate_fixtures.py",
        "generated_at": utc_to_iso(dt.datetime.now(tz=UTC)),
        "swisseph_version": swe.version,
        "pyswisseph": _module_version("swisseph"),
        "ephemeris": eph_label,
        "ayanamsha": "SIDM_LAHIRI",
        "seed": _SEED_USED,
        "python": platform.python_version(),
        "count": count,
        "ut_note": "All Julian Days are UT; inputs treated as UTC (UT ~= UTC here).",
        "notes": notes,
    }


def _module_version(name: str) -> str:
    try:
        module = __import__(name)
        return str(getattr(module, "version", "unknown"))
    except ImportError:  # pragma: no cover
        return "unknown"


# --------------------------------------------------------------------------- #
# Entry point
# --------------------------------------------------------------------------- #


def write_json(path: str, data: Dict[str, object], compact: bool) -> None:
    os.makedirs(os.path.dirname(path), exist_ok=True)
    with open(path, "w", encoding="utf-8") as handle:
        if compact:
            json.dump(data, handle, separators=(",", ":"))
        else:
            json.dump(data, handle, indent=1)
        handle.write("\n")
    size_kb = os.path.getsize(path) / 1024.0
    print(f"  wrote {path} ({size_kb:.0f} KB)", flush=True)


def main(argv: Optional[List[str]] = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[1])
    parser.add_argument(
        "--ephemeris",
        choices=("moshier", "swiss"),
        default="moshier",
        help="ephemeris to use (default: moshier; swiss requires data files)",
    )
    parser.add_argument(
        "--ephe-path",
        default=None,
        help="path to Swiss Ephemeris data files (for --ephemeris swiss)",
    )
    parser.add_argument("--seed", type=int, default=SEED, help="RNG seed (default: %(default)s)")
    parser.add_argument(
        "--out-dir",
        default=None,
        help="output directory (default: <repo>/tests/fixtures/reference)",
    )
    parser.add_argument(
        "--quick",
        action="store_true",
        help="generate a tiny dataset (for pipeline testing only)",
    )
    args = parser.parse_args(argv)

    repo_root = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
    out_dir = args.out_dir or os.path.join(repo_root, "tests", "fixtures", "reference")

    _set_seed(args.seed)

    n_longitudes = 40 if args.quick else 2000
    n_transitions = 10 if args.quick else 300
    event_years = EVENT_YEARS[:1] if args.quick else EVENT_YEARS

    flags, eph_label = setup_ephemeris(args.ephemeris, args.ephe_path)
    swe.set_sid_mode(swe.SIDM_LAHIRI)
    rng = Lcg(args.seed)

    print(f"Generating fixtures with swisseph {swe.version} ({eph_label}) -> {out_dir}")

    print("1/3 body_longitudes ...")
    write_json(
        os.path.join(out_dir, "body_longitudes.json"),
        generate_body_longitudes(n_longitudes, flags, eph_label, rng),
        compact=True,
    )

    print("2/3 transitions ...")
    write_json(
        os.path.join(out_dir, "transitions.json"),
        generate_transitions(n_transitions, flags, eph_label, rng),
        compact=True,
    )

    print("3/3 sun_moon_events ...")
    write_json(
        os.path.join(out_dir, "sun_moon_events.json"),
        generate_events(flags, eph_label, event_years),
        compact=True,
    )

    print("Done.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
