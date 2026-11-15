<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Panchang;
use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\Tests\Support\TestLocations;

/**
 * The exact (lunar-anchored) Samvat years must be wired through the whole
 * facade stack, and callers must be able to tell exact from approximate.
 *
 *   • Facade/service calendar() WITH a location → exact years
 *     (samvatIsApproximate = false).
 *   • Without a location → the documented date-only approximations
 *     (samvatIsApproximate = true).
 *   • PanchangResult (getForDate) carries gujaratiSamvat from the exact
 *     method and exposes it in toArray().
 */
final class SamvatWiringTest extends TestCase
{
    private const AHMEDABAD = 'Asia/Kolkata';

    public function testFacadeCalendarWithLocationUsesExactSamvat(): void
    {
        $facade = Panchang::createDefault();
        $location = TestLocations::ahmedabad();

        // Yugadi 2025 = 2025-03-30.
        $before = $facade->calendar(new DateTimeImmutable('2025-03-29 12:00', new DateTimeZone(self::AHMEDABAD)), $location);
        $after = $facade->calendar(new DateTimeImmutable('2025-03-31 12:00', new DateTimeZone(self::AHMEDABAD)), $location);

        self::assertSame(2081, $before->vikramSamvat);
        self::assertSame(2082, $after->vikramSamvat);
        self::assertFalse($before->samvatIsApproximate, 'with a location the Samvat years must be exact');
        self::assertFalse($after->samvatIsApproximate);
    }

    public function testFacadeCalendarWithLocationExactGujarati(): void
    {
        $facade = Panchang::createDefault();
        $location = TestLocations::ahmedabad();

        // Diwali 2025-10-21, Gujarati New Year 2025-10-22.
        $before = $facade->calendar(new DateTimeImmutable('2025-10-21 12:00', new DateTimeZone(self::AHMEDABAD)), $location);
        $after = $facade->calendar(new DateTimeImmutable('2025-10-25 12:00', new DateTimeZone(self::AHMEDABAD)), $location);

        self::assertSame(2081, $before->gujaratiSamvat);
        self::assertSame(2082, $after->gujaratiSamvat);
    }

    public function testServiceCalendarWithLocationUsesExactSamvat(): void
    {
        $service = PanchangService::createDefault();
        $location = TestLocations::ahmedabad();

        $before = $service->calendar(new DateTimeImmutable('2025-03-29 12:00', new DateTimeZone(self::AHMEDABAD)), $location);
        $after = $service->calendar(new DateTimeImmutable('2025-03-31 12:00', new DateTimeZone(self::AHMEDABAD)), $location);

        self::assertSame(2081, $before->vikramSamvat);
        self::assertSame(2082, $after->vikramSamvat);
        self::assertFalse($before->samvatIsApproximate);
        self::assertFalse($after->samvatIsApproximate);

        // Gujarati through the service as well.
        self::assertSame(2081, $service->calendar(new DateTimeImmutable('2025-10-21 12:00', new DateTimeZone(self::AHMEDABAD)), $location)->gujaratiSamvat);
        self::assertSame(2082, $service->calendar(new DateTimeImmutable('2025-10-25 12:00', new DateTimeZone(self::AHMEDABAD)), $location)->gujaratiSamvat);
    }

    public function testCalendarWithoutLocationIsExplicitlyApproximate(): void
    {
        $facade = Panchang::createDefault();

        $result = $facade->calendar(new DateTimeImmutable('2025-03-31 12:00', new DateTimeZone(self::AHMEDABAD)));

        self::assertTrue($result->samvatIsApproximate, 'no location → the documented approximation must be flagged');
        // The April-1 approximation has not flipped yet on Mar 31.
        self::assertSame(2081, $result->vikramSamvat);

        $service = PanchangService::createDefault();
        self::assertTrue(
            $service->calendar(new DateTimeImmutable('2025-03-31 12:00', new DateTimeZone(self::AHMEDABAD)))->samvatIsApproximate
        );
    }

    public function testGetForDateCarriesExactGujaratiSamvat(): void
    {
        $service = PanchangService::createDefault();
        $location = TestLocations::ahmedabad();

        $before = $service->getForDate(new DateTimeImmutable('2025-10-21', new DateTimeZone(self::AHMEDABAD)), $location);
        $after = $service->getForDate(new DateTimeImmutable('2025-10-25', new DateTimeZone(self::AHMEDABAD)), $location);

        self::assertSame(2081, $before->gujaratiSamvat);
        self::assertSame(2082, $after->gujaratiSamvat);

        $array = $after->toArray();
        self::assertArrayHasKey('gujaratiSamvat', $array['samvat']);
        self::assertSame(2082, $array['samvat']['gujaratiSamvat']);
    }
}
