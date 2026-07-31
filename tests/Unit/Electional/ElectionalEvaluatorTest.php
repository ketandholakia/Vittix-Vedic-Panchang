<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Electional;

use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Electional\ElectionalEvaluator;
use Vittix\Panchang\Enum\Karana;
use Vittix\Panchang\Enum\Nakshatra;

final class ElectionalEvaluatorTest extends TestCase
{
    public function testAmritSiddhiYoga(): void
    {
        $evaluator = new ElectionalEvaluator();

        // Thursday + Pushya
        self::assertTrue($evaluator->hasAmritSiddhiYoga(4, Nakshatra::Pushya));
        // Monday + Mrigashira
        self::assertTrue($evaluator->hasAmritSiddhiYoga(1, Nakshatra::Mrigashira));
        // Monday + Pushya (Not Amrit Siddhi)
        self::assertFalse($evaluator->hasAmritSiddhiYoga(1, Nakshatra::Pushya));
    }

    public function testGuruPushyaYoga(): void
    {
        $evaluator = new ElectionalEvaluator();

        self::assertTrue($evaluator->hasGuruPushyaYoga(4, Nakshatra::Pushya));
        self::assertFalse($evaluator->hasGuruPushyaYoga(3, Nakshatra::Pushya)); // Wed
    }

    public function testDishaShool(): void
    {
        $evaluator = new ElectionalEvaluator();

        self::assertSame('West', $evaluator->getDishaShool(0)); // Sun
        self::assertSame('East', $evaluator->getDishaShool(1)); // Mon
        self::assertSame('South', $evaluator->getDishaShool(4)); // Thu
    }

    public function testPanchakaDosha(): void
    {
        $evaluator = new ElectionalEvaluator();

        self::assertTrue($evaluator->isPanchakaDosha(Nakshatra::Revati));
        self::assertTrue($evaluator->isPanchakaDosha(Nakshatra::Dhanishtha));
        self::assertFalse($evaluator->isPanchakaDosha(Nakshatra::Ashwini));
    }

    public function testBhadra(): void
    {
        $evaluator = new ElectionalEvaluator();

        self::assertTrue($evaluator->isBhadraActive(Karana::Vishti));
        self::assertFalse($evaluator->isBhadraActive(Karana::Bava));
    }
}
