<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

/**
 * The six environment probes are shipped by the plugin: an application gets them without a line of configuration.
 */
final class EnvironmentChecksTest extends KernelTestCase
{
    private const IDENTIFIERS = [
        'environment.php_version',
        'environment.symfony_version',
        'environment.sylius_version',
        'environment.php_extensions',
        'environment.opcache',
        'environment.app_debug',
    ];

    public function testThePluginShipsTheSixEnvironmentProbesWithoutAnyConfiguration(): void
    {
        $statuses = $this->statuses();

        foreach (self::IDENTIFIERS as $identifier) {
            self::assertArrayHasKey($identifier, $statuses);
        }
    }

    public function testTheTestEnvironmentIsNotProductionSoTheDebugProbeWarns(): void
    {
        self::assertSame(HealthStatus::Warning, $this->statuses()['environment.app_debug'] ?? null);
    }

    public function testTheVersionProbesMeasureTheInstalledStack(): void
    {
        $statuses = $this->statuses();

        foreach (['environment.php_version', 'environment.symfony_version', 'environment.sylius_version'] as $identifier) {
            self::assertNotSame(HealthStatus::Unknown, $statuses[$identifier] ?? HealthStatus::Unknown, $identifier);
        }
    }

    public function testTheExtensionsProbeIsOkOnTheTestApplicationWhichNeedsThem(): void
    {
        self::assertSame(HealthStatus::Ok, $this->statuses()['environment.php_extensions'] ?? null);
    }

    /**
     * @return array<string, HealthStatus>
     */
    private function statuses(): array
    {
        self::bootKernel();
        $runner = self::getContainer()->get('tmonier_sylius_platform_health.test.runner');
        self::assertInstanceOf(HealthCheckRunner::class, $runner);

        $statuses = [];
        foreach ($runner->run() as $report) {
            self::assertInstanceOf(HealthCheckReport::class, $report);
            $statuses[$report->identifier] = $report->result->status;
        }

        return $statuses;
    }
}
