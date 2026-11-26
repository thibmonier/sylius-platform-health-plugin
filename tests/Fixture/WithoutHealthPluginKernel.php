<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\DemoWebhooksBundle;

/**
 * The application of a shop that installed the demonstration plugin and not the health plugin: only the framework and the
 * demonstration plugin, whose bundle is built with a check that answers "the interface does not exist". The Sylius test
 * application cannot be used for that, the health plugin is part of it. It lists the service ids before the container is
 * optimized, because an unused service is removed afterwards.
 */
final class WithoutHealthPluginKernel extends Kernel implements CompilerPassInterface
{
    /** @var list<string> */
    private array $serviceIds = [];

    private readonly string $workDir;

    public function __construct()
    {
        parent::__construct('test', true);
        $this->workDir = sys_get_temp_dir() . '/tmonier_platform_health_without_plugin_' . bin2hex(random_bytes(6));
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DemoWebhooksBundle(static fn (string $interface): bool => false);
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(static function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', ['secret' => 'without-health-plugin', 'http_method_override' => false]);
        });
    }

    public function process(ContainerBuilder $container): void
    {
        $this->serviceIds = array_map(strval(...), array_keys($container->getDefinitions()));
    }

    /**
     * @return list<string>
     */
    public function serviceIds(): array
    {
        return $this->serviceIds;
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__, 2);
    }

    public function getCacheDir(): string
    {
        return $this->workDir . '/cache';
    }

    public function getBuildDir(): string
    {
        return $this->workDir . '/cache';
    }

    public function getLogDir(): string
    {
        return $this->workDir . '/log';
    }

    public function workDir(): string
    {
        return $this->workDir;
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass($this, PassConfig::TYPE_BEFORE_OPTIMIZATION);
    }
}
