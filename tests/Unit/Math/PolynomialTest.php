<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Math;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Math\Polynomial;

final class PolynomialTest extends TestCase
{
    public function testConstant(): void
    {
        // f(x) = 7
        self::assertEqualsWithDelta(7.0, Polynomial::evaluate(99.0, 7.0), 1e-10);
    }

    public function testLinear(): void
    {
        // f(x) = 2 + 3x  →  f(4) = 14
        self::assertEqualsWithDelta(14.0, Polynomial::evaluate(4.0, 2.0, 3.0), 1e-10);
    }

    public function testQuadratic(): void
    {
        // f(x) = 1 + 2x + 3x²  →  f(2) = 1+4+12 = 17
        self::assertEqualsWithDelta(17.0, Polynomial::evaluate(2.0, 1.0, 2.0, 3.0), 1e-10);
    }

    public function testCubic(): void
    {
        // f(x) = x³  →  f(3) = 27
        self::assertEqualsWithDelta(27.0, Polynomial::evaluate(3.0, 0.0, 0.0, 0.0, 1.0), 1e-10);
    }

    public function testZeroCoefficients(): void
    {
        self::assertEqualsWithDelta(0.0, Polynomial::evaluate(100.0, 0.0, 0.0, 0.0), 1e-10);
    }

    public function testEmptyCoefficients(): void
    {
        self::assertEqualsWithDelta(0.0, Polynomial::evaluate(5.0), 1e-10);
    }

    public function testNegativeX(): void
    {
        // f(x) = 1 + x + x²  →  f(-2) = 1-2+4 = 3
        self::assertEqualsWithDelta(3.0, Polynomial::evaluate(-2.0, 1.0, 1.0, 1.0), 1e-10);
    }
}
