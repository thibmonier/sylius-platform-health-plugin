<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
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
        // T-303-02 loads config/health_checks.php here when the interface exists.
        ($this->interfaceExists)(HealthCheckInterface::class);
    }
}
