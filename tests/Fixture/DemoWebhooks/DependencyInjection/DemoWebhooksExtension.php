<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;

final class DemoWebhooksExtension extends Extension
{
    /** @var \Closure(string): bool */
    private readonly \Closure $interfaceExists;

    /**
     * @param (\Closure(string): bool)|null $interfaceExists
     */
    public function __construct(?\Closure $interfaceExists = null)
    {
        $this->interfaceExists = $interfaceExists ?? interface_exists(...);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        // HealthCheckInterface::class is only a string here: it does not load the interface, so a shop without the health plugin never hits a fatal error.
        if (!($this->interfaceExists)(HealthCheckInterface::class)) {
            return;
        }

        (new PhpFileLoader($container, new FileLocator(__DIR__ . '/../config')))->load('health_checks.php');
    }
}
