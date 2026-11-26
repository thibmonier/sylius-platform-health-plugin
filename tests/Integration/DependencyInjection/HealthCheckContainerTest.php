<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\DependencyInjection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\DemoDataCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\DemoEnvironmentCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\DemoShopCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\DuplicateMigrationsCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\MigrationsCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\StatefulIdentifierCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\PluginChecks;
use Tmonier\SyliusPlatformHealthPlugin\DependencyInjection\Compiler\UniqueIdentifierPass;
use Tmonier\SyliusPlatformHealthPlugin\DependencyInjection\TmonierSyliusPlatformHealthExtension;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;
use Tmonier\SyliusPlatformHealthPlugin\TmonierSyliusPlatformHealthPlugin;

/**
 * A plain Symfony container: no Sylius kernel, no database.
 */
final class HealthCheckContainerTest extends TestCase
{
    public function testAutoconfiguredChecksAreRegisteredWithoutAnyConfiguration(): void
    {
        $container = $this->container(DemoEnvironmentCheck::class, DemoDataCheck::class, DemoShopCheck::class);

        $reports = $this->applicationReports($container);

        self::assertSame(
            [
                ['environment.demo', HealthCategory::Environment, HealthStatus::Ok],
                ['data.demo', HealthCategory::Data, HealthStatus::Warning],
                ['shop.demo', HealthCategory::Shop, HealthStatus::Critical],
            ],
            array_map(static fn (HealthCheckReport $report): array => [$report->identifier, $report->category, $report->result->status], $reports),
        );
    }

    public function testAProjectWithoutAnyCheckOfItsOwnGetsOnlyTheFifteenChecksOfThePlugin(): void
    {
        self::assertSame([], $this->applicationReports($this->container()));
        self::assertCount(15, $this->runner($this->container())->run());
    }

    public function testTwoChecksWithTheSameIdentifierFailTheCompilationNamingTheIdentifierAndBothClasses(): void
    {
        $container = $this->container(MigrationsCheck::class, DuplicateMigrationsCheck::class);

        $failure = $this->compilationFailure($container);

        self::assertNotNull($failure, 'The compilation should have failed.');
        self::assertStringContainsString('data.migrations', $failure->getMessage());
        self::assertStringContainsString(MigrationsCheck::class, $failure->getMessage());
        self::assertStringContainsString(DuplicateMigrationsCheck::class, $failure->getMessage());
    }

    public function testACheckWhoseIdentifierDependsOnItsConstructorFailsTheCompilationNamingTheClass(): void
    {
        $container = $this->container(StatefulIdentifierCheck::class);

        $failure = $this->compilationFailure($container);

        self::assertNotNull($failure, 'The compilation should have failed.');
        self::assertStringContainsString(StatefulIdentifierCheck::class, $failure->getMessage());
    }

    public function testThePluginRegistersThePassThatChecksTheIdentifiers(): void
    {
        $container = new ContainerBuilder();

        (new TmonierSyliusPlatformHealthPlugin())->build($container);

        $passes = array_map(static fn (object $pass): string => $pass::class, $container->getCompilerPassConfig()->getBeforeOptimizationPasses());
        self::assertContains(UniqueIdentifierPass::class, $passes);
    }

    private function container(string ...$checkClasses): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new TmonierSyliusPlatformHealthExtension());
        $container->loadFromExtension('tmonier_sylius_platform_health');
        $container->register('logger', NullLogger::class);
        // The environment probes of the plugin read the date through the clock service and two kernel parameters.
        $container->register('clock', NativeClock::class);
        // The cache and directory probes read the application cache and three kernel paths.
        $container->register('cache.app', ArrayAdapter::class);
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->setParameter('kernel.cache_dir', sys_get_temp_dir());
        $container->setParameter('kernel.logs_dir', sys_get_temp_dir());
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'test');
        // The data probes read the default Doctrine connection: an in-memory SQLite one, opened only if a probe queries it.
        $container->register('doctrine.dbal.default_connection', Connection::class)
            ->setFactory([DriverManager::class, 'getConnection'])
            ->setArguments([['driver' => 'pdo_sqlite', 'memory' => true]])
        ;
        foreach ($checkClasses as $checkClass) {
            $container->register($checkClass, $checkClass)->setAutoconfigured(true);
        }
        $container->setAlias('test.runner', 'tmonier_sylius_platform_health.runner')->setPublic(true);
        (new TmonierSyliusPlatformHealthPlugin())->build($container);

        return $container;
    }

    private function compilationFailure(ContainerBuilder $container): ?\Throwable
    {
        try {
            $container->compile();
        } catch (\Throwable $failure) {
            return $failure;
        }

        return null;
    }

    /**
     * @return list<HealthCheckReport> the reports of the checks of the application, without the fifteen of the plugin
     */
    private function applicationReports(ContainerBuilder $container): array
    {
        return array_values(array_filter(
            $this->runner($container)->run(),
            static fn (HealthCheckReport $report): bool => !PluginChecks::ships($report->identifier),
        ));
    }

    private function runner(ContainerBuilder $container): HealthCheckRunner
    {
        $container->compile();
        $runner = $container->get('test.runner');
        self::assertInstanceOf(HealthCheckRunner::class, $runner);

        return $runner;
    }
}
