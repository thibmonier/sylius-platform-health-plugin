<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Environment;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpKernel\Kernel;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Environment\EndOfSupportTable;
use Tmonier\SyliusPlatformHealthPlugin\Environment\SupportAssessor;
use Tmonier\SyliusPlatformHealthPlugin\Environment\SymfonyVersionCheck;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class SymfonyVersionCheckTest extends TestCase
{
    use ResultAssertions;

    public function testASupportedKernelIsOkAndDoesNotNeedTheTable(): void
    {
        $result = $this->execute('11/2028', '11/2029', '2026-11-23');

        self::assertSummary($result, HealthStatus::Ok, 'tmonier_platform_health.environment.support.ok', ['%name%' => 'Symfony', '%version%' => '7.4.1', '%date%' => '2029-11-30']);
        self::assertDetail($result, 'tmonier_platform_health.environment.support.detail.kernel');
    }

    public function testNinetyDaysBeforeTheEndOfLifeItWarns(): void
    {
        $result = $this->execute('05/2026', '11/2026', '2026-09-01');

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.environment.support.warning', ['%date%' => '2026-11-30']);
    }

    public function testTheDayAfterTheEndOfLifeItIsCritical(): void
    {
        self::assertSame(HealthStatus::Critical, $this->execute('05/2026', '11/2026', '2026-12-01')->status);
        self::assertSame(HealthStatus::Warning, $this->execute('05/2026', '11/2026', '2026-11-30')->status);
    }

    public function testAnEndedMaintenanceWithSecurityFixesLeftWarns(): void
    {
        $result = $this->execute('05/2026', '05/2029', '2026-06-15');

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.environment.support.maintenance_ended', ['%date%' => '2029-05-31']);
    }

    public function testAnUnreadableConstantIsUnknownWithoutAnException(): void
    {
        $result = $this->execute('soon', '11/2029', '2026-11-23');

        self::assertSummary($result, HealthStatus::Unknown, 'tmonier_platform_health.environment.support.unreadable', ['%name%' => 'Symfony']);
    }

    public function testTheRealKernelConstantsAreReadByDefault(): void
    {
        $table = new EndOfSupportTable();
        $check = new SymfonyVersionCheck(new SupportAssessor(new MockClock('2026-11-23', 'UTC'), $table), Kernel::VERSION, Kernel::END_OF_MAINTENANCE, Kernel::END_OF_LIFE);

        self::assertNotSame(HealthStatus::Unknown, $check->run()->status);
    }

    private function execute(string $maintenance, string $life, string $today): HealthResult
    {
        $assessor = new SupportAssessor(new MockClock($today . ' 10:00:00', 'UTC'), new EndOfSupportTable());

        return (new SymfonyVersionCheck($assessor, '7.4.1', $maintenance, $life))->run();
    }
}
