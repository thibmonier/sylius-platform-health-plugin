<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Yaml\Yaml;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;

final class TmonierSyliusPlatformHealthExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{time_budget_ms: int, cache_ttl: int, access: array{role: string}, environment: array{support_warning_days: int}, data: array{connection_warning_ms: int, table_growth: array<string, int>}} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('tmonier_sylius_platform_health.time_budget_ms', $config['time_budget_ms']);
        $container->setParameter('tmonier_sylius_platform_health.cache_ttl', $config['cache_ttl']);
        $container->setParameter('tmonier_sylius_platform_health.access.role', $config['access']['role']);
        $container->setParameter('tmonier_sylius_platform_health.environment.support_warning_days', $config['environment']['support_warning_days']);
        $container->setParameter('tmonier_sylius_platform_health.data.connection_warning_ms', $config['data']['connection_warning_ms']);
        $container->setParameter('tmonier_sylius_platform_health.data.table_growth', $config['data']['table_growth']);
        $container->registerForAutoconfiguration(HealthCheckInterface::class)->addTag(HealthCheckInterface::TAG);

        (new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config')))->load('services.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        // The hooks of the page are part of the plugin: the application has nothing to import for them.
        /** @var array{sylius_twig_hooks: array<string, mixed>} $hooks */
        $hooks = Yaml::parseFile(__DIR__ . '/../../config/twig_hooks/admin.yaml');
        $container->prependExtensionConfig('sylius_twig_hooks', $hooks['sylius_twig_hooks']);
    }

    public function getAlias(): string
    {
        return 'tmonier_sylius_platform_health';
    }
}
