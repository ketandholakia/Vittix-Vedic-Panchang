<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Math;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Math\Angle;

final class AngleTest extends TestCase
{
    // -------------------------------------------------------------------------
    // normalize()
    // -------------------------------------------------------------------------

    public function testNormalizeZero(): void
    {
        self::assertEqualsWithDelta(0.0, Angle::normalize(0.0), 1e-10);
    }

    public function testNormalize360(): void
    {
        // 360 should collapse to 0 (half-open interval)
        self::assertEqualsWithDelta(0.0, Angle::normalize(360.0), 1e-10);
    }

    public function testNormalize720(): void
    {
        self::assertEqualsWithDelta(0.0, Angle::normalize(720.0), 1e-10);
    }

    public function testNormalizeNegative1(): void
    {
        self::assertEqualsWithDelta(359.0, Angle::normalize(-1.0), 1e-10);
    }

    public function testNormalizeNegative360(): void
    {
        self::assertEqualsWithDelta(0.0, Angle::normalize(-360.0), 1e-10);
    }

    public function testNormalizeLargePositive(): void
    {
        self::assertEqualsWithDelta(45.0, Angle::normalize(3645.0), 1e-9);
    }

    public function testNormalizeLargeNegative(): void
    {
        self::assertEqualsWithDelta(270.0, Angle::normalize(-90.0), 1e-10);
    }

    public function testNormalizeResult_is_always_in_range(): void
    {
        $testValues = [-1000.5, -360.0, -1.0, 0.0, 1.0, 180.0, 359.9999, 360.0, 721.5];

        foreach ($testValues as $v) {
            $result = Angle::normalize($v);
            self::assertGreaterThanOrEqual(0.0, $result, "normalize({$v}) < 0");
            self::assertLessThan(360.0, $result, "normalize({$v}) >= 360");
        }
    }

    // -------------------------------------------------------------------------
    // normalizeSigned()
    // -------------------------------------------------------------------------

    public function testNormalizeSignedPositive90(): void
    {
        self::assertEqualsWithDelta(90.0, Angle::normalizeSigned(90.0), 1e-10);
    }

    public function testNormalizeSignedNegative90(): void
    {
        self::assertEqualsWithDelta(-90.0, Angle::normalizeSigned(270.0), 1e-10);
    }

    public function testNormalizeSignedZero(): void
    {
        self::assertEqualsWithDelta(0.0, Angle::normalizeSigned(0.0), 1e-10);
    }

    public function testNormalizeSignedPlus180(): void
    {
        // 180 maps to 180 (boundary)
        self::assertEqualsWithDelta(180.0, Angle::normalizeSigned(180.0), 1e-10);
    }

    // -------------------------------------------------------------------------
    // Trig helpers
    // -------------------------------------------------------------------------

    public function testSin90(): void
    {
        self::assertEqualsWithDelta(1.0, Angle::sin(90.0), 1e-10);
    }

    public function testCos0(): void
    {
        self::assertEqualsWithDelta(1.0, Angle::cos(0.0), 1e-10);
    }

    public function testAtan2(): void
    {
        self::assertEqualsWithDelta(45.0, Angle::atan2(1.0, 1.0), 1e-10);
    }

    public function testRoundTrip(): void
    {
        $deg = 123.456;
        self::assertEqualsWithDelta($deg, Angle::toDegrees(Angle::toRadians($deg)), 1e-10);
    }
}
