<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology;

use Vittix\Panchang\Enum\Body;
use Vittix\Panchang\Enum\Dignity;
use Vittix\Panchang\Enum\Rashi;

/**
 * Calculates the planetary dignity based on its Rashi (and degree for Moolatrikona).
 */
final class DignityCalculator
{
    /**
     * Determines the dignity of a planet.
     *
     * Note: PARTIAL — implements the primary states (Exalted, Debilitated,
     * Moolatrikona, Own Sign). Returns null for planets that fall into the
     * secondary states (Friendly/Neutral/Enemy), which are not yet
     * implemented; callers must handle null accordingly.
     *
     * @param float $longitude The planet's ecliptic longitude, used only to derive the
     *                          degree-within-sign for the Exalted/Moolatrikona/OwnSign split.
     *                          This MUST be the longitude that actually places the planet in
     *                          $rashi — i.e. degrees within the D1 sign for a D1 call. For
     *                          divisional (Varga) charts, $rashi is a *different* sign than the
     *                          one $longitude's degree-within-sign belongs to, so pass
     *                          $applyDegreeRefinement: false rather than the raw D1 longitude;
     *                          Moolatrikona is a D1-specific concept and doesn't have a
     *                          principled meaning against a varga-mapped sign.
     * @param bool  $applyDegreeRefinement When true (default, D1 behaviour), planets whose
     *                          dignity depends on exact degree (the Exalted/Moolatrikona split,
     *                          or Moolatrikona/OwnSign split) are refined using $longitude. When
     *                          false, those same signs collapse to their coarser classification
     *                          (Exalted stays Exalted; the Moolatrikona-eligible portion of an
     *                          own sign is reported simply as OwnSign) — the only correct
     *                          behaviour when $longitude isn't actually within $rashi, as is
     *                          always the case for Varga charts.
     */
    public function calculate(
        Body $body,
        Rashi $rashi,
        float $longitude,
        bool $applyDegreeRefinement = true,
    ): ?Dignity {
        $degree = $applyDegreeRefinement ? fmod($longitude, 30.0) : null;

        return match ($body) {
            Body::Sun => $this->sunDignity($rashi, $degree),
            Body::Moon => $this->moonDignity($rashi, $degree),
            Body::Mars => $this->marsDignity($rashi, $degree),
            Body::Mercury => $this->mercuryDignity($rashi, $degree),
            Body::Jupiter => $this->jupiterDignity($rashi, $degree),
            Body::Venus => $this->venusDignity($rashi, $degree),
            Body::Saturn => $this->saturnDignity($rashi, $degree),
            Body::RahuMean => $this->rahuDignity($rashi),
            Body::KetuMean => $this->ketuDignity($rashi),
        };
    }

    private function sunDignity(Rashi $rashi, ?float $degree): ?Dignity
    {
        if ($rashi === Rashi::Mesha) {
            return Dignity::Exalted;
        }
        if ($rashi === Rashi::Tula) {
            return Dignity::Debilitated;
        }
        if ($rashi === Rashi::Simha) {
            if ($degree === null) {
                return Dignity::OwnSign;
            }
            return ($degree >= 0.0 && $degree <= 20.0) ? Dignity::Moolatrikona : Dignity::OwnSign;
        }
        return null;
    }

    private function moonDignity(Rashi $rashi, ?float $degree): ?Dignity
    {
        if ($rashi === Rashi::Vrishabha) {
            // Without a meaningful degree (e.g. in a Varga context), Vrishabha
            // is unambiguously the Moon's exaltation sign — report Exalted.
            if ($degree === null) {
                return Dignity::Exalted;
            }
            return ($degree <= 3.0) ? Dignity::Exalted : Dignity::Moolatrikona;
        }
        if ($rashi === Rashi::Vrischika) {
            return Dignity::Debilitated;
        }
        if ($rashi === Rashi::Karka) {
            return Dignity::OwnSign;
        }
        return null;
    }

    private function marsDignity(Rashi $rashi, ?float $degree): ?Dignity
    {
        if ($rashi === Rashi::Makara) {
            return Dignity::Exalted;
        }
        if ($rashi === Rashi::Karka) {
            return Dignity::Debilitated;
        }
        if ($rashi === Rashi::Mesha) {
            if ($degree === null) {
                return Dignity::OwnSign;
            }
            return ($degree <= 12.0) ? Dignity::Moolatrikona : Dignity::OwnSign;
        }
        if ($rashi === Rashi::Vrischika) {
            return Dignity::OwnSign;
        }
        return null;
    }

    private function mercuryDignity(Rashi $rashi, ?float $degree): ?Dignity
    {
        if ($rashi === Rashi::Meena) {
            return Dignity::Debilitated;
        }
        if ($rashi === Rashi::Kanya) {
            if ($degree === null) {
                return Dignity::OwnSign;
            }
            if ($degree <= 15.0) {
                return Dignity::Exalted;
            }
            if ($degree > 15.0 && $degree <= 20.0) {
                return Dignity::Moolatrikona;
            }
            return Dignity::OwnSign;
        }
        if ($rashi === Rashi::Mithuna) {
            return Dignity::OwnSign;
        }
        return null;
    }

    private function jupiterDignity(Rashi $rashi, ?float $degree): ?Dignity
    {
        if ($rashi === Rashi::Karka) {
            return Dignity::Exalted;
        }
        if ($rashi === Rashi::Makara) {
            return Dignity::Debilitated;
        }
        if ($rashi === Rashi::Dhanu) {
            if ($degree === null) {
                return Dignity::OwnSign;
            }
            return ($degree <= 10.0) ? Dignity::Moolatrikona : Dignity::OwnSign;
        }
        if ($rashi === Rashi::Meena) {
            return Dignity::OwnSign;
        }
        return null;
    }

    private function venusDignity(Rashi $rashi, ?float $degree): ?Dignity
    {
        if ($rashi === Rashi::Meena) {
            return Dignity::Exalted;
        }
        if ($rashi === Rashi::Kanya) {
            return Dignity::Debilitated;
        }
        if ($rashi === Rashi::Tula) {
            if ($degree === null) {
                return Dignity::OwnSign;
            }
            return ($degree <= 15.0) ? Dignity::Moolatrikona : Dignity::OwnSign;
        }
        if ($rashi === Rashi::Vrishabha) {
            return Dignity::OwnSign;
        }
        return null;
    }

    private function saturnDignity(Rashi $rashi, ?float $degree): ?Dignity
    {
        if ($rashi === Rashi::Tula) {
            return Dignity::Exalted;
        }
        if ($rashi === Rashi::Mesha) {
            return Dignity::Debilitated;
        }
        if ($rashi === Rashi::Kumbha) {
            if ($degree === null) {
                return Dignity::OwnSign;
            }
            return ($degree <= 20.0) ? Dignity::Moolatrikona : Dignity::OwnSign;
        }
        if ($rashi === Rashi::Makara) {
            return Dignity::OwnSign;
        }
        return null;
    }

    private function rahuDignity(Rashi $rashi): ?Dignity
    {
        if ($rashi === Rashi::Vrishabha) {
            return Dignity::Exalted;
        }
        if ($rashi === Rashi::Vrischika) {
            return Dignity::Debilitated;
        }
        return null;
    }

    private function ketuDignity(Rashi $rashi): ?Dignity
    {
        if ($rashi === Rashi::Vrischika) {
            return Dignity::Exalted;
        }
        if ($rashi === Rashi::Vrishabha) {
            return Dignity::Debilitated;
        }
        return null;
    }
}
