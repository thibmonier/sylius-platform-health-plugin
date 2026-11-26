<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Fixture;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\DependencyInjection\DemoWebhooksExtension;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\Health\BrokenCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\Health\OutboxCheck;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;

/**
 * The extension of the demonstration plugin on a bare container: the probes are loaded only when the health plugin exists.
 */
final class DemoWebhooksExtensionTest extends TestCase
{
    public function testTheProbesAreLoadedWhenTheHealthPluginExists(): void
    {
        $container = $this->load(static fn (string $interface): bool => true);

        self::assertTrue($container->hasDefinition('demo_webhooks.health.outbox'));
        self::assertSame(OutboxCheck::class, $container->getDefinition('demo_webhooks.health.outbox')->getClass());
        self::assertSame(BrokenCheck::class, $container->getDefinition('demo_webhooks.health.broken')->getClass());
    }

    public function testTheProbesAreAutoconfiguredSoTheHealthPluginTagsThem(): void
    {
        $container = $this->load(static fn (string $interface): bool => true);

        self::assertTrue($container->hasDefinition('demo_webhooks.health.outbox'));
        self::assertTrue($container->getDefinition('demo_webhooks.health.outbox')->isAutoconfigured());
        self::assertFalse($container->getDefinition('demo_webhooks.health.outbox')->hasTag(HealthCheckInterface::TAG), 'The plugin never tags by hand: the health plugin does.');
    }

    public function testTheProbesAreLoadedByDefaultSinceTheInterfaceExistsInThisRepository(): void
    {
        $container = new ContainerBuilder();

        (new DemoWebhooksExtension())->load([], $container);

        self::assertTrue($container->hasDefinition('demo_webhooks.health.outbox'));
    }

    public function testNoServiceIsLoadedWhenTheHealthPluginDoesNotExist(): void
    {
        $container = $this->load(static fn (string $interface): bool => false);

        self::assertSame([], array_diff(array_keys($container->getDefinitions()), ['service_container']));
    }

    public function testTheCheckIsAskedAboutTheInterfaceOfTheHealthPlugin(): void
    {
        $asked = [];

        $this->load(static function (string $interface) use (&$asked): bool {
            $asked[] = $interface;

            return false;
        });

        self::assertSame([HealthCheckInterface::class], $asked);
    }

    /**
     * @param \Closure(string): bool $interfaceExists
     */
    private function load(\Closure $interfaceExists): ContainerBuilder
    {
        $container = new ContainerBuilder();

        (new DemoWebhooksExtension($interfaceExists))->load([], $container);

        return $container;
    }
}
