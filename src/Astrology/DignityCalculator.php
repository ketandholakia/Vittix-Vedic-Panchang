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
     * Note: Currently implements the primary states (Exalted, Debilitated, Moolatrikona, Own Sign).
     * Falls back to null if it does not fall into these primary categories (Friendly/Neutral/Enemy to be added).
     */
    public function calculate(Body $body, Rashi $rashi, float $longitude): ?Dignity
    {
        $degreeInSign = fmod($longitude, 30.0);

        return match ($body) {
            Body::Sun => $this->sunDignity($rashi, $degreeInSign),
            Body::Moon => $this->moonDignity($rashi, $degreeInSign),
            Body::Mars => $this->marsDignity($rashi, $degreeInSign),
            Body::Mercury => $this->mercuryDignity($rashi, $degreeInSign),
            Body::Jupiter => $this->jupiterDignity($rashi, $degreeInSign),
            Body::Venus => $this->venusDignity($rashi, $degreeInSign),
            Body::Saturn => $this->saturnDignity($rashi, $degreeInSign),
            Body::RahuMean => $this->rahuDignity($rashi),
            Body::KetuMean => $this->ketuDignity($rashi),
            default => null,
        };
    }

    private function sunDignity(Rashi $rashi, float $degree): ?Dignity
    {
        if ($rashi === Rashi::Mesha) {
            return Dignity::Exalted;
        }
        if ($rashi === Rashi::Tula) {
            return Dignity::Debilitated;
        }
        if ($rashi === Rashi::Simha) {
            return ($degree >= 0.0 && $degree <= 20.0) ? Dignity::Moolatrikona : Dignity::OwnSign;
        }
        return null;
    }

    private function moonDignity(Rashi $rashi, float $degree): ?Dignity
    {
        if ($rashi === Rashi::Vrishabha) {
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

    private function marsDignity(Rashi $rashi, float $degree): ?Dignity
    {
        if ($rashi === Rashi::Makara) {
            return Dignity::Exalted;
        }
        if ($rashi === Rashi::Karka) {
            return Dignity::Debilitated;
        }
        if ($rashi === Rashi::Mesha) {
            return ($degree <= 12.0) ? Dignity::Moolatrikona : Dignity::OwnSign;
        }
        if ($rashi === Rashi::Vrischika) {
            return Dignity::OwnSign;
        }
        return null;
    }

    private function mercuryDignity(Rashi $rashi, float $degree): ?Dignity
    {
        if ($rashi === Rashi::Meena) {
            return Dignity::Debilitated;
        }
        if ($rashi === Rashi::Kanya) {
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

    private function jupiterDignity(Rashi $rashi, float $degree): ?Dignity
    {
        if ($rashi === Rashi::Karka) {
            return Dignity::Exalted;
        }
        if ($rashi === Rashi::Makara) {
            return Dignity::Debilitated;
        }
        if ($rashi === Rashi::Dhanu) {
            return ($degree <= 10.0) ? Dignity::Moolatrikona : Dignity::OwnSign;
        }
        if ($rashi === Rashi::Meena) {
            return Dignity::OwnSign;
        }
        return null;
    }

    private function venusDignity(Rashi $rashi, float $degree): ?Dignity
    {
        if ($rashi === Rashi::Meena) {
            return Dignity::Exalted;
        }
        if ($rashi === Rashi::Kanya) {
            return Dignity::Debilitated;
        }
        if ($rashi === Rashi::Tula) {
            return ($degree <= 15.0) ? Dignity::Moolatrikona : Dignity::OwnSign;
        }
        if ($rashi === Rashi::Vrishabha) {
            return Dignity::OwnSign;
        }
        return null;
    }

    private function saturnDignity(Rashi $rashi, float $degree): ?Dignity
    {
        if ($rashi === Rashi::Tula) {
            return Dignity::Exalted;
        }
        if ($rashi === Rashi::Mesha) {
            return Dignity::Debilitated;
        }
        if ($rashi === Rashi::Kumbha) {
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

