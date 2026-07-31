<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Panchanga;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\Vara;
use Vittix\Panchang\Panchanga\VaraCalculator;
use Vittix\Panchang\Time\AstroTimeFactory;
use Vittix\Panchang\ValueObject\GeoLocation;
use Vittix\Panchang\Tests\Support\MockAstronomyEngine;

final class VaraCalculatorTest extends TestCase
{
    private VaraCalculator $calculator;

    protected function setUp(): void
    {
        // Engine is passed in to determine sunrise/sunset
        $this->calculator = new VaraCalculator();
    }

    public function testVaraAtDifferentTimesOfDay(): void
    {
        $timezone = new DateTimeZone('Asia/Kolkata');
        // Saturday
        $date = new DateTimeImmutable('2026-08-01 00:00:00', $timezone);
        $sunrise = new DateTimeImmutable('2026-08-01 06:10:00', $timezone);

        // Before local sunrise (say 04:00), Vara belongs to previous day (Friday)
        $earlyMorning = new DateTimeImmutable('2026-08-01 04:00:00', $timezone);
        $varaEarly = $this->calculator->calculate($earlyMorning, $sunrise);
        self::assertSame(Vara::Shukravara, $varaEarly);

        // After local sunrise (say 10:00), Vara belongs to current day (Saturday)
        $morning = new DateTimeImmutable('2026-08-01 10:00:00', $timezone);
        $varaMorning = $this->calculator->calculate($morning, $sunrise);
        self::assertSame(Vara::Shanivara, $varaMorning);
    }
}
