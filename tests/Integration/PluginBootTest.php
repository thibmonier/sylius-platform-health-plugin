<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;
use Tmonier\SyliusPlatformHealthPlugin\TmonierSyliusPlatformHealthPlugin;

final class PluginBootTest extends KernelTestCase
{
    public function testThePluginIsRegisteredInTheTestApplication(): void
    {
        $kernel = self::bootKernel();

        self::assertArrayHasKey(TmonierSyliusPlatformHealthPlugin::class, $this->bundleClasses($kernel->getBundles()));
    }

    public function testTheRunnerFindsTheChecksOfTheApplicationWithoutAnyConfiguration(): void
    {
        self::bootKernel();
        $runner = self::getContainer()->get('tmonier_sylius_platform_health.test.runner');
        self::assertInstanceOf(HealthCheckRunner::class, $runner);

        $reports = $runner->run();

        self::assertSame(
            [
                'environment.demo' => HealthStatus::Ok,
                'data.demo' => HealthStatus::Warning,
                'shop.demo' => HealthStatus::Critical,
                'plugins.demo' => HealthStatus::Unknown,
                'plugins.failing' => HealthStatus::Unknown,
            ],
            array_column(array_map(static fn (HealthCheckReport $report): array => [$report->identifier, $report->result->status], $reports), 1, 0),
        );
    }

    /**
     * @param array<string, object> $bundles
     *
     * @return array<class-string, true>
     */
    private function bundleClasses(array $bundles): array
    {
        $classes = [];
        foreach ($bundles as $bundle) {
            $classes[$bundle::class] = true;
        }

        return $classes;
    }
}
