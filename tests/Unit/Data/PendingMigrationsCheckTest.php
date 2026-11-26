<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Data;

use PHPUnit\Framework\TestCase;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Data\MigrationStatus;
use Tmonier\SyliusPlatformHealthPlugin\Data\MigrationStatusReaderInterface;
use Tmonier\SyliusPlatformHealthPlugin\Data\PendingMigrationsCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class PendingMigrationsCheckTest extends TestCase
{
    use ResultAssertions;

    public function testEveryMigrationExecutedIsOk(): void
    {
        self::assertSummary($this->execute(new MigrationStatus(0, 0)), HealthStatus::Ok, 'tmonier_platform_health.data.pending_migrations.ok');
    }

    public function testAForgottenMigrationIsCriticalWithTheCountAndTheDocumentation(): void
    {
        $result = $this->execute(new MigrationStatus(2, 0));

        self::assertSummary($result, HealthStatus::Critical, 'tmonier_platform_health.data.pending_migrations.pending', ['%count%' => 2]);
        self::assertEquals(DocumentationLink::create(), $result->action);
    }

    public function testAnExecutedMigrationUnknownToTheCodeWarns(): void
    {
        $result = $this->execute(new MigrationStatus(0, 1));

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.data.pending_migrations.unknown_executed', ['%count%' => 1]);
        self::assertEquals(DocumentationLink::create(), $result->action);
    }

    public function testPendingMigrationsWinOverUnknownOnes(): void
    {
        self::assertSame(HealthStatus::Critical, $this->execute(new MigrationStatus(1, 3))->status);
    }

    public function testAnApplicationWithoutMigrationsIsUnknownNeverOk(): void
    {
        self::assertSummary($this->execute(null), HealthStatus::Unknown, 'tmonier_platform_health.data.pending_migrations.not_configured');
    }

    private function execute(?MigrationStatus $status): HealthResult
    {
        return (new PendingMigrationsCheck(new class($status) implements MigrationStatusReaderInterface {
            public function __construct(private readonly ?MigrationStatus $status)
            {
            }

            public function read(): ?MigrationStatus
            {
                return $this->status;
            }
        }))->run();
    }
}
