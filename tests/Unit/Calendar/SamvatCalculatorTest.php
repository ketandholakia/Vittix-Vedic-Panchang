<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Unit\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Calendar\SamvatCalculator;

final class SamvatCalculatorTest extends TestCase
{
    private SamvatCalculator $calc;

    protected function setUp(): void
    {
        $this->calc = new SamvatCalculator();
    }

    // -----------------------------------------------------------------------
    // Vikram Samvat
    // -----------------------------------------------------------------------

    public function testVikramSamvat_January(): void
    {
        // Jan 2026 — before Chaitra Shukla Pratipada → VS 2082
        $date = new DateTimeImmutable('2026-01-15', new DateTimeZone('Asia/Kolkata'));
        self::assertSame(2082, $this->calc->vikramSamvat($date));
    }

    public function testVikramSamvat_April(): void
    {
        // Apr 2026 — after new year → VS 2083
        $date = new DateTimeImmutable('2026-04-15', new DateTimeZone('Asia/Kolkata'));
        self::assertSame(2083, $this->calc->vikramSamvat($date));
    }

    public function testVikramSamvat_December(): void
    {
        // Dec 2026 → VS 2083
        $date = new DateTimeImmutable('2026-12-01', new DateTimeZone('Asia/Kolkata'));
        self::assertSame(2083, $this->calc->vikramSamvat($date));
    }

    // -----------------------------------------------------------------------
    // Shaka Samvat
    // -----------------------------------------------------------------------

    public function testShakaSamvat_BeforeMarch22(): void
    {
        // Feb 2026 → before Chaitra 1 → Shaka 1947
        $date = new DateTimeImmutable('2026-02-10', new DateTimeZone('Asia/Kolkata'));
        self::assertSame(1947, $this->calc->shakaSamvat($date));
    }

    public function testShakaSamvat_AfterMarch22(): void
    {
        // Apr 2026 → after Chaitra 1 → Shaka 1948
        $date = new DateTimeImmutable('2026-04-01', new DateTimeZone('Asia/Kolkata'));
        self::assertSame(1948, $this->calc->shakaSamvat($date));
    }

    public function testShakaSamvat_March22(): void
    {
        // March 22, 2026 (non-leap) = Chaitra 1 → Shaka 1948
        $date = new DateTimeImmutable('2026-03-22', new DateTimeZone('Asia/Kolkata'));
        self::assertSame(1948, $this->calc->shakaSamvat($date));
    }

    public function testShakaSamvat_December(): void
    {
        // Dec 2026 → Shaka 1948
        $date = new DateTimeImmutable('2026-12-31', new DateTimeZone('Asia/Kolkata'));
        self::assertSame(1948, $this->calc->shakaSamvat($date));
    }

    // -----------------------------------------------------------------------
    // Gujarati Samvat
    // -----------------------------------------------------------------------

    public function testGujaratiSamvat_October(): void
    {
        // Oct 2026 (before Diwali new year ~Nov) → GS 2082
        $date = new DateTimeImmutable('2026-10-01', new DateTimeZone('Asia/Kolkata'));
        self::assertSame(2082, $this->calc->gujaratiSamvat($date));
    }

    public function testGujaratiSamvat_November(): void
    {
        // Nov 2026 (after Diwali new year) → GS 2083
        $date = new DateTimeImmutable('2026-11-15', new DateTimeZone('Asia/Kolkata'));
        self::assertSame(2083, $this->calc->gujaratiSamvat($date));
    }
}
