<?php

declare(strict_types=1);

namespace Vittix\Panchang\Astrology;

use Vittix\Panchang\Astrology\Yoga\GajakesariYoga;
use Vittix\Panchang\Astrology\Yoga\PanchaMahapurusha\BhadraYoga;
use Vittix\Panchang\Astrology\Yoga\PanchaMahapurusha\HamsaYoga;
use Vittix\Panchang\Astrology\Yoga\PanchaMahapurusha\MalavyaYoga;
use Vittix\Panchang\Astrology\Yoga\PanchaMahapurusha\RuchakaYoga;
use Vittix\Panchang\Astrology\Yoga\PanchaMahapurusha\SashaYoga;
use Vittix\Panchang\Astrology\Yoga\YogaRuleInterface;
use Vittix\Panchang\ValueObject\Kundali;
use Vittix\Panchang\ValueObject\Yoga;

/**
 * Detects classical planetary combinations (Yogas) in a birth chart.
 */
final class YogaDetector
{
    /** @var YogaRuleInterface[] */
    private array $rules = [];

    public function __construct()
    {
        // Register all built-in yogas
        $this->rules = [
            new GajakesariYoga(),
            new RuchakaYoga(),
            new BhadraYoga(),
            new HamsaYoga(),
            new MalavyaYoga(),
            new SashaYoga(),
        ];
    }

    /**
     * Scan the Kundali for all known Yogas.
     *
     * @return Yoga[] List of detected Yogas.
     */
    public function detect(Kundali $kundali): array
    {
        $detected = [];

        foreach ($this->rules as $rule) {
            $yoga = $rule->detect($kundali);
            if ($yoga !== null) {
                $detected[] = $yoga;
            }
        }

        return $detected;
    }
}
