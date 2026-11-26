<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\WithoutHealthPluginKernel;

/**
 * A shop that installed the plugin that provides a probe, and not the health plugin: the kernel boots, and no probe is registered.
 */
final class WithoutHealthPluginKernelTest extends TestCase
{
    private WithoutHealthPluginKernel $kernel;

    protected function setUp(): void
    {
        $this->kernel = new WithoutHealthPluginKernel();
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
        $workDir = $this->kernel->workDir();
        if (str_starts_with($workDir, sys_get_temp_dir() . '/tmonier_platform_health_without_plugin_')) {
            (new Filesystem())->remove($workDir);
        }
    }

    public function testTheKernelBootsWithTheContainerCompiled(): void
    {
        $this->kernel->boot();

        self::assertSame('test', $this->kernel->getContainer()->getParameter('kernel.environment'));
        self::assertArrayHasKey('DemoWebhooksBundle', $this->kernel->getBundles());
    }

    public function testNoServiceOfTheDemonstrationPluginIsRegistered(): void
    {
        $this->kernel->boot();

        self::assertNotSame([], $this->kernel->serviceIds(), 'The container was not built, nothing could be checked.');
        self::assertSame([], array_values(array_filter($this->kernel->serviceIds(), static fn (string $id): bool => str_starts_with($id, 'demo_webhooks.'))));
    }
}
