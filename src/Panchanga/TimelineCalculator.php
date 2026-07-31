<?php

declare(strict_types=1);

namespace Vittix\Panchang\Panchanga;

use Vittix\Panchang\Enum\Karana;
use Vittix\Panchang\Enum\Nakshatra;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Enum\Yoga;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\ValueObject\AstroInstant;

/**
 * Calculates transition timelines (windows) for Panchanga limbs.
 */
final class TimelineCalculator
{
    public function __construct(
        private readonly TransitionFinder $finder,
        private readonly TithiCalculator $tithiCalc,
        private readonly NakshatraCalculator $nakshatraCalc,
        private readonly YogaCalculator $yogaCalc,
        private readonly KaranaCalculator $karanaCalc,
    ) {
    }

    /**
     * Get all Tithi transitions and their active windows between two instants.
     *
     * @return array<int, array{tithi: Tithi, start: AstroInstant, end: ?AstroInstant}>
     */
    public function getTithiWindows(AstroInstant $from, AstroInstant $until): array
    {
        $transitions = $this->finder->findAll($from, $until, function(AstroInstant $inst) {
            return $this->tithiCalc->calculate($inst)->value;
        });

        $windows = [];
        $currentStart = $from;

        foreach ($transitions as $transition) {
            $windows[] = [
                'tithi' => $this->tithiCalc->calculate($currentStart),
                'start' => $currentStart,
                'end'   => $transition,
            ];
            $currentStart = $transition;
        }

        $windows[] = [
            'tithi' => $this->tithiCalc->calculate($currentStart),
            'start' => $currentStart,
            'end'   => $until,
        ];

        return $windows;
    }

    /**
     * Get all Nakshatra transitions and their active windows between two instants.
     *
     * @return array<int, array{nakshatra: Nakshatra, start: AstroInstant, end: ?AstroInstant}>
     */
    public function getNakshatraWindows(AstroInstant $from, AstroInstant $until): array
    {
        $transitions = $this->finder->findAll($from, $until, function(AstroInstant $inst) {
            return $this->nakshatraCalc->calculate($inst)->value;
        });

        $windows = [];
        $currentStart = $from;

        foreach ($transitions as $transition) {
            $windows[] = [
                'nakshatra' => $this->nakshatraCalc->calculate($currentStart),
                'start'     => $currentStart,
                'end'       => $transition,
            ];
            $currentStart = $transition;
        }

        $windows[] = [
            'nakshatra' => $this->nakshatraCalc->calculate($currentStart),
            'start'     => $currentStart,
            'end'       => $until,
        ];

        return $windows;
    }

    /**
     * Get all Yoga transitions and their active windows between two instants.
     *
     * @return array<int, array{yoga: Yoga, start: AstroInstant, end: ?AstroInstant}>
     */
    public function getYogaWindows(AstroInstant $from, AstroInstant $until): array
    {
        $transitions = $this->finder->findAll($from, $until, function(AstroInstant $inst) {
            return $this->yogaCalc->calculate($inst)->value;
        });

        $windows = [];
        $currentStart = $from;

        foreach ($transitions as $transition) {
            $windows[] = [
                'yoga'  => $this->yogaCalc->calculate($currentStart),
                'start' => $currentStart,
                'end'   => $transition,
            ];
            $currentStart = $transition;
        }

        $windows[] = [
            'yoga'  => $this->yogaCalc->calculate($currentStart),
            'start' => $currentStart,
            'end'   => $until,
        ];

        return $windows;
    }

    /**
     * Get all Karana transitions and their active windows between two instants.
     *
     * @return array<int, array{karana: Karana, start: AstroInstant, end: ?AstroInstant}>
     */
    public function getKaranaWindows(AstroInstant $from, AstroInstant $until): array
    {
        $transitions = $this->finder->findAll($from, $until, function(AstroInstant $inst) {
            return $this->karanaCalc->calculate($inst)->value;
        });

        $windows = [];
        $currentStart = $from;

        foreach ($transitions as $transition) {
            $windows[] = [
                'karana' => $this->karanaCalc->calculate($currentStart),
                'start'  => $currentStart,
                'end'    => $transition,
            ];
            $currentStart = $transition;
        }

        $windows[] = [
            'karana' => $this->karanaCalc->calculate($currentStart),
            'start'  => $currentStart,
            'end'    => $until,
        ];

        return $windows;
    }
}
