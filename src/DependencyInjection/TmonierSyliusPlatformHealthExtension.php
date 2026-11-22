<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;

final class TmonierSyliusPlatformHealthExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{time_budget_ms: int} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('tmonier_sylius_platform_health.time_budget_ms', $config['time_budget_ms']);
        $container->registerForAutoconfiguration(HealthCheckInterface::class)->addTag(HealthCheckInterface::TAG);

        (new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config')))->load('services.php');
    }

    public function getAlias(): string
    {
        return 'tmonier_sylius_platform_health';
    }
}
