<?php

declare(strict_types=1);

namespace Vittix\Panchang\ValueObject;

/**
 * Accuracy metadata attached to Panchang results.
 *
 * When a Tithi or Nakshatra transition falls very close to sunrise the
 * observance date is uncertain even with a perfect ephemeris.  This object
 * lets callers surface that uncertainty rather than silently returning a
 * potentially wrong date.
 */
final readonly class AccuracyInfo
{
    public function __construct(
        /** Estimated angular error of the underlying ephemeris, in degrees. */
        public readonly float $estimatedAngularErrorDegrees,

        /** Resulting estimated timing error for transitions, in seconds. */
        public readonly float $estimatedTimeErrorSeconds,

        /**
         * True when a Panchang boundary (Tithi/Nakshatra/Yoga/Karana change)
         * falls within the estimated error window of sunrise.
         * Callers should display a caution to the user.
         */
        public readonly bool $nearBoundary,
    ) {
    }

    /**
     * Default accuracy for the pure-PHP Meeus engine.
     * Angular error ~0.05° → timing error ~6 min for the Moon.
     */
    public static function standard(): self
    {
        return new self(
            estimatedAngularErrorDegrees: 0.05,
            estimatedTimeErrorSeconds: 360.0,
            nearBoundary: false,
        );
    }

    public function withNearBoundary(bool $nearBoundary): self
    {
        return new self(
            $this->estimatedAngularErrorDegrees,
            $this->estimatedTimeErrorSeconds,
            $nearBoundary,
        );
    }
}
