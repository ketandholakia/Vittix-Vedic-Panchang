<?php

declare(strict_types=1);

namespace Vittix\Panchang\Tests\Integration\Panchanga;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Vittix\Panchang\Enum\Karana;
use Vittix\Panchang\Enum\Nakshatra;
use Vittix\Panchang\Enum\Paksha;
use Vittix\Panchang\Enum\Tithi;
use Vittix\Panchang\Enum\Vara;
use Vittix\Panchang\Enum\Yoga;
use Vittix\Panchang\Panchanga\PanchangService;
use Vittix\Panchang\ValueObject\GeoLocation;

/**
 * Extensive oracle tests against known reference data (Drik Panchang, Kalnirnay, etc.)
 * for various dates to ensure all 5 limbs align perfectly for a given location.
 */
final class PanchangOracleTest extends TestCase
{
    private PanchangService $service;

    protected function setUp(): void
    {
        $this->service = PanchangService::createDefault();
    }

    /**
     * @dataProvider panchangOracleProvider
     */
    public function testPanchangAgainstOracle(
        string $dateTime,
        string $timezone,
        float $lat,
        float $lon,
        Tithi $expectedTithi,
        Paksha $expectedPaksha,
        Nakshatra $expectedNakshatra,
        Yoga $expectedYoga,
        Karana $expectedKarana,
        Vara $expectedVara
    ): void {
        $date = new DateTimeImmutable($dateTime, new DateTimeZone($timezone));
        $location = new GeoLocation($lat, $lon);

        $result = $this->service->getForDate($date, $location);

        self::assertSame($expectedTithi, $result->tithi, "Tithi mismatch for {$dateTime}");
        self::assertSame($expectedPaksha, $result->paksha, "Paksha mismatch for {$dateTime}");
        self::assertSame($expectedNakshatra, $result->nakshatra, "Nakshatra mismatch for {$dateTime}");
        self::assertSame($expectedYoga, $result->yoga, "Yoga mismatch for {$dateTime}");
        self::assertSame($expectedKarana, $result->karana, "Karana mismatch for {$dateTime}");
        self::assertSame($expectedVara, $result->vara, "Vara mismatch for {$dateTime}");
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function panchangOracleProvider(): array
    {
        // Coordinates for Ahmedabad, IN
        $ahdLat = 23.0225;
        $ahdLon = 72.5714;
        $ist = 'Asia/Kolkata';

        return [
            // Format: Date, Timezone, Lat, Lon, Tithi, Paksha, Nakshatra, Yoga, Karana, Vara
            '2026-08-05 (Ahmedabad)' => [
                '2026-08-05 06:00:00', $ist, $ahdLat, $ahdLon,
                Tithi::KrishnaD7, Paksha::Krishna, Nakshatra::Ashwini, Yoga::Shoola, Karana::Vishti, Vara::Budhavara
            ],
            '2026-01-14 Makar Sankranti (Ahmedabad)' => [
                '2026-01-14 06:00:00', $ist, $ahdLat, $ahdLon,
                Tithi::KrishnaD11, Paksha::Krishna, Nakshatra::Anuradha, Yoga::Ganda, Karana::Balava, Vara::Budhavara
            ],
            '2024-03-25 Holi (Ahmedabad)' => [
                '2024-03-25 06:00:00', $ist, $ahdLat, $ahdLon,
                Tithi::Purnima, Paksha::Shukla, Nakshatra::UttaraPhalguni, Yoga::Vriddhi, Karana::Bava, Vara::Somavara
            ],
            '2024-10-31 Diwali (Ahmedabad)' => [
                '2024-10-31 06:00:00', $ist, $ahdLat, $ahdLon,
                Tithi::KrishnaChaturdashi, Paksha::Krishna, Nakshatra::Chitra, Yoga::Vishkumbha, Karana::Shakuni, Vara::Guruvara
            ],
            '2024-01-22 Ram Mandir Pratishtha (Ayodhya)' => [
                '2024-01-22 06:00:00', $ist, 26.7922, 82.1998,
                Tithi::ShuklaD12, Paksha::Shukla, Nakshatra::Mrigashira, Yoga::Brahma, Karana::Bava, Vara::Somavara
            ],
            '2023-08-15 Independence Day (New Delhi)' => [
                '2023-08-15 06:00:00', $ist, 28.6139, 77.2090,
                Tithi::KrishnaChaturdashi, Paksha::Krishna, Nakshatra::Pushya, Yoga::Vyatipata, Karana::Shakuni, Vara::Mangalavara
            ],
            '2025-01-01 New Year (London)' => [
                '2025-01-01 06:00:00', 'Europe/London', 51.5074, -0.1278,
                Tithi::ShuklaD2, Paksha::Shukla, Nakshatra::UttaraAshadha, Yoga::Vyaghata, Karana::Balava, Vara::Budhavara
            ],
            '2025-03-14 Holi (New York)' => [
                '2025-03-14 06:00:00', 'America/New_York', 40.7128, -74.0060,
                Tithi::KrishnaPratipada, Paksha::Krishna, Nakshatra::UttaraPhalguni, Yoga::Ganda, Karana::Balava, Vara::Shukravara
            ],
            '2025-10-21 Diwali (Sydney)' => [
                '2025-10-21 06:00:00', 'Australia/Sydney', -33.8688, 151.2093,
                Tithi::Amavasya, Paksha::Krishna, Nakshatra::Chitra, Yoga::Vaidhriti, Karana::Chatushpada, Vara::Mangalavara
            ],
            '2024-02-29 Leap Day (Tokyo)' => [
                '2024-02-29 06:00:00', 'Asia/Tokyo', 35.6762, 139.6503,
                Tithi::KrishnaD4, Paksha::Krishna, Nakshatra::Chitra, Yoga::Vriddhi, Karana::Balava, Vara::Guruvara
            ]
        ];
    }
}
