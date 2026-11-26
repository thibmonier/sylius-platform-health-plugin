<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Data;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\RecordingLogger;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Data\ConnectionCheck;
use Tmonier\SyliusPlatformHealthPlugin\Data\ConnectionPingerInterface;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class ConnectionCheckTest extends TestCase
{
    use ResultAssertions;

    private const DSN = 'mysql://shop_user:S3cr3tPassw0rd@db-primary.internal:3306/shop';

    public function testADatabaseThatAnswersQuicklyIsOkWithItsDuration(): void
    {
        $result = $this->execute(120);

        self::assertSummary($result, HealthStatus::Ok, 'tmonier_platform_health.data.connection.ok', ['%duration_ms%' => 120]);
    }

    public function testADatabaseSlowerThanTheThresholdWarns(): void
    {
        $result = $this->execute(750);

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.data.connection.slow', ['%duration_ms%' => 750, '%threshold_ms%' => 500]);
    }

    public function testTheThresholdIsConfigurable(): void
    {
        self::assertSame(HealthStatus::Ok, $this->execute(750, 1000)->status);
    }

    public function testAnUnreachableDatabaseIsCriticalAndNeverShowsTheDsn(): void
    {
        $logger = new RecordingLogger();
        $result = $this->execute(10, 500, new \RuntimeException('An exception occurred in the driver: ' . self::DSN), $logger);

        self::assertSummary($result, HealthStatus::Critical, 'tmonier_platform_health.data.connection.unreachable');
        self::assertEquals(DocumentationLink::create(), $result->action);
        foreach (['S3cr3tPassw0rd', 'db-primary.internal', 'shop_user', 'mysql://'] as $secret) {
            self::assertStringNotContainsString($secret, self::serialized($result));
        }
        self::assertNotEmpty($logger->records, 'The cause is logged for the operator, never shown.');
    }

    private function execute(int $durationMs, int $warningMs = 500, ?\Throwable $failure = null, ?RecordingLogger $logger = null): HealthResult
    {
        $clock = new MockClock('2026-11-25 10:00:00');
        $pinger = new class($clock, $durationMs, $failure) implements ConnectionPingerInterface {
            public function __construct(private readonly MockClock $clock, private readonly int $durationMs, private readonly ?\Throwable $failure)
            {
            }

            public function ping(): void
            {
                $this->clock->sleep($this->durationMs / 1000);
                if ($this->failure instanceof \Throwable) {
                    throw $this->failure;
                }
            }
        };

        return (new ConnectionCheck($pinger, $clock, $logger ?? new RecordingLogger(), $warningMs))->run();
    }
}
