<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Environment;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Environment\EndOfSupportTable;
use Tmonier\SyliusPlatformHealthPlugin\Environment\SupportAssessor;
use Tmonier\SyliusPlatformHealthPlugin\Environment\SyliusVersionCheck;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class SyliusVersionCheckTest extends TestCase
{
    use ResultAssertions;

    private EndOfSupportTable $table;

    protected function setUp(): void
    {
        $this->table = new EndOfSupportTable();
    }

    public function testASupportedBranchIsOkAndNamesTheTable(): void
    {
        $result = $this->execute('2.3.3', $this->end('2.3')->modify('-300 days'));

        self::assertSummary($result, HealthStatus::Ok, 'tmonier_platform_health.environment.support.ok', ['%name%' => 'Sylius', '%version%' => '2.3.3']);
        self::assertDetail($result, 'tmonier_platform_health.environment.support.detail.table', ['%table%' => '2026-12']);
    }

    public function testAVersionPrefixedWithVIsRead(): void
    {
        self::assertSame(HealthStatus::Ok, $this->execute('v2.3.3', $this->end('2.3')->modify('-300 days'))->status);
    }

    public function testNinetyDaysBeforeTheEndItWarns(): void
    {
        $end = $this->end('2.2');

        $result = $this->execute('2.2.9', $end->modify('-90 days'));

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.environment.support.warning', ['%days%' => 90, '%date%' => $end->format('Y-m-d')]);
    }

    public function testTheDayAfterTheEndItIsCritical(): void
    {
        self::assertSame(HealthStatus::Critical, $this->execute('2.2.9', $this->end('2.2')->modify('+1 day'))->status);
    }

    public function testABranchAbsentFromTheTableIsUnknownNeverOk(): void
    {
        $result = $this->execute('9.9.0', new \DateTimeImmutable('2026-11-23', new \DateTimeZone('UTC')));

        self::assertSummary($result, HealthStatus::Unknown, 'tmonier_platform_health.environment.support.branch_missing', ['%name%' => 'Sylius']);
    }

    public function testADevelopmentVersionIsUnknownWithoutAnException(): void
    {
        $result = $this->execute('dev-main', new \DateTimeImmutable('2026-11-23', new \DateTimeZone('UTC')));

        self::assertSummary($result, HealthStatus::Unknown, 'tmonier_platform_health.environment.support.unreadable', ['%name%' => 'Sylius']);
    }

    public function testAStaleTableNeverGivesAnOk(): void
    {
        $now = new \DateTimeImmutable('2027-12-02', new \DateTimeZone('UTC'));

        self::assertSame(HealthStatus::Warning, $this->execute('2.3.3', $now)->status);
    }

    public function testAStaleTableWarnsToRenewItWhenTheBranchIsFarFromItsEnd(): void
    {
        $assessor = new SupportAssessor(new MockClock('2027-12-02', 'UTC'), $this->table);

        $result = $assessor->assessTable('Sylius', '2.3.3', new \DateTimeImmutable('2035-01-01', new \DateTimeZone('UTC')));

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.environment.support.table_stale', ['%table%' => '2026-12']);
    }

    public function testWithoutAnExplicitVersionItReadsTheInstalledSylius(): void
    {
        $check = new SyliusVersionCheck(new SupportAssessor(new MockClock('2026-11-23', 'UTC'), $this->table), $this->table);

        self::assertNotSame(HealthStatus::Unknown, $check->run()->status);
    }

    private function end(string $branch): \DateTimeImmutable
    {
        $end = $this->table->syliusSupportEnd($branch);
        self::assertInstanceOf(\DateTimeImmutable::class, $end);

        return $end;
    }

    private function execute(string $version, \DateTimeImmutable $now): HealthResult
    {
        return (new SyliusVersionCheck(new SupportAssessor(new MockClock($now), $this->table), $this->table, $version))->run();
    }
}
