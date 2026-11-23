<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Environment;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Environment\EndOfSupportTable;
use Tmonier\SyliusPlatformHealthPlugin\Environment\PhpVersionCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\SupportAssessor;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class PhpVersionCheckTest extends TestCase
{
    use ResultAssertions;

    private EndOfSupportTable $table;

    protected function setUp(): void
    {
        $this->table = new EndOfSupportTable();
    }

    public function testASupportedBranchFarFromItsEndIsOkAndNamesTheTable(): void
    {
        $end = $this->end('8.4');

        $result = $this->execute('8.4.3', $end->modify('-400 days'));

        self::assertSummary($result, HealthStatus::Ok, 'tmonier_platform_health.environment.support.ok', ['%name%' => 'PHP', '%version%' => '8.4.3', '%days%' => 400]);
        self::assertDetail($result, 'tmonier_platform_health.environment.support.detail.table', ['%table%' => '2026-12']);
    }

    public function testNinetyDaysBeforeTheEndItWarnsWithTheDaysLeftAndTheDate(): void
    {
        $end = $this->end('8.3');

        $result = $this->execute('8.3.12', $end->modify('-90 days'));

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.environment.support.warning', ['%days%' => 90, '%date%' => $end->format('Y-m-d')]);
        self::assertDetail($result, 'tmonier_platform_health.environment.support.detail.table', ['%table%' => '2026-12']);
    }

    public function testExactlyOneHundredEightyDaysBeforeTheEndItWarnsAndOneMoreDayItIsOk(): void
    {
        $end = $this->end('8.3');

        self::assertSame(HealthStatus::Warning, $this->execute('8.3.1', $end->modify('-180 days'))->status);
        self::assertSame(HealthStatus::Ok, $this->execute('8.3.1', $end->modify('-181 days'))->status);
    }

    public function testTheDayAfterTheEndItIsCriticalButTheLastDayItIsStillAWarning(): void
    {
        $end = $this->end('8.3');

        self::assertSame(HealthStatus::Warning, $this->execute('8.3.1', $end)->status);
        $critical = $this->execute('8.3.1', $end->modify('+1 day'));
        self::assertSummary($critical, HealthStatus::Critical, 'tmonier_platform_health.environment.support.critical', ['%date%' => $end->format('Y-m-d')]);
    }

    public function testABranchAbsentFromTheTableIsUnknownNeverOk(): void
    {
        $result = $this->execute('9.9.0', new \DateTimeImmutable('2026-11-23', new \DateTimeZone('UTC')));

        self::assertSummary($result, HealthStatus::Unknown, 'tmonier_platform_health.environment.support.branch_missing', ['%version%' => '9.9.0']);
        self::assertDetail($result, 'tmonier_platform_health.environment.support.detail.table', ['%table%' => '2026-12']);
    }

    public function testAStaleTableWarnsInsteadOfAFalseOk(): void
    {
        $result = $this->execute('8.5.0', new \DateTimeImmutable('2027-12-02', new \DateTimeZone('UTC')));

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.environment.support.table_stale', ['%table%' => '2026-12']);
    }

    public function testAStaleTableDoesNotHideAnEndedSupport(): void
    {
        $result = $this->execute('8.2.0', new \DateTimeImmutable('2028-06-01', new \DateTimeZone('UTC')));

        self::assertSame(HealthStatus::Critical, $result->status);
    }

    public function testAnUnreadableVersionIsUnknownWithoutAnException(): void
    {
        $result = $this->execute('not-a-version', new \DateTimeImmutable('2026-11-23', new \DateTimeZone('UTC')));

        self::assertSummary($result, HealthStatus::Unknown, 'tmonier_platform_health.environment.support.unreadable', ['%name%' => 'PHP']);
    }

    public function testTheSummaryCarriesNoPath(): void
    {
        $result = $this->execute('8.4.3', $this->end('8.4')->modify('-10 days'));

        self::assertStringNotContainsString('/', self::serialized($result));
    }

    public function testWithoutAnExplicitVersionItReadsTheRunningPhp(): void
    {
        $check = new PhpVersionCheck($this->assessor(new MockClock('2026-11-23', 'UTC')), $this->table);

        self::assertNotSame(HealthStatus::Unknown, $check->run()->status);
    }

    public function testTheLinkPointsToTheDocumentationOfTheNonOkResults(): void
    {
        $result = $this->execute('8.3.1', $this->end('8.3')->modify('-10 days'));

        self::assertNotNull($result->action);
        self::assertSame('tmonier_platform_health_documentation', $result->action->route);
    }

    private function end(string $branch): \DateTimeImmutable
    {
        $end = $this->table->phpSecurityEnd($branch);
        self::assertInstanceOf(\DateTimeImmutable::class, $end);

        return $end;
    }

    private function execute(string $version, \DateTimeImmutable $now): HealthResult
    {
        return (new PhpVersionCheck($this->assessor(new MockClock($now)), $this->table, $version))->run();
    }

    private function assessor(MockClock $clock): SupportAssessor
    {
        return new SupportAssessor($clock, $this->table);
    }
}
