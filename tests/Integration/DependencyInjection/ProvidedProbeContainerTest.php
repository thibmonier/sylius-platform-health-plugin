<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\DependencyInjection\DemoWebhooksExtension;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\BuildsHealthContainer;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;

/**
 * The probes of a plugin that does not require the health plugin, on a plain Symfony container where both are present.
 */
final class ProvidedProbeContainerTest extends TestCase
{
    use BuildsHealthContainer;

    public function testTheProbeOfThePluginCarriesTheTagOfTheProbes(): void
    {
        $container = $this->containerWith(static fn (string $interface): bool => true);

        $container->compile();

        self::assertArrayHasKey('demo_webhooks.health.outbox', $container->findTaggedServiceIds(HealthCheckInterface::TAG));
    }

    public function testTheRunnerRunsTheProbeOfThePluginInItsCategory(): void
    {
        $container = $this->containerWith(static fn (string $interface): bool => true);

        $reports = $this->reports($container);

        self::assertArrayHasKey('demo_webhooks.outbox', $reports);
        self::assertSame(HealthCategory::Plugins, $reports['demo_webhooks.outbox']->category);
    }

    public function testWithoutTheHealthPluginTheCompiledContainerHasNoProbeOfThePlugin(): void
    {
        $container = $this->containerWith(static fn (string $interface): bool => false);

        $reports = $this->reports($container);

        self::assertSame([], array_filter(array_keys($reports), static fn (string $identifier): bool => str_starts_with($identifier, 'demo_webhooks.')));
        self::assertFalse($container->hasDefinition('demo_webhooks.health.outbox'));
    }

    /**
     * @param \Closure(string): bool $interfaceExists
     */
    private function containerWith(\Closure $interfaceExists): ContainerBuilder
    {
        $container = $this->container();
        $container->registerExtension(new DemoWebhooksExtension($interfaceExists));
        $container->loadFromExtension('demo_webhooks');

        return $container;
    }

    /**
     * @return array<string, HealthCheckReport> by identifier
     */
    private function reports(ContainerBuilder $container): array
    {
        $container->compile();
        $runner = $container->get('test.runner');
        self::assertInstanceOf(HealthCheckRunner::class, $runner);

        $reports = [];
        foreach ($runner->run() as $report) {
            $reports[$report->identifier] = $report;
        }

        return $reports;
    }
}
