<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Yaml\Yaml;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\ScheduledTasks\BeatInterval;

final class TmonierSyliusPlatformHealthExtension extends Extension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    public function load(array $configs, ContainerBuilder $container): void
    {
        /** @var array{time_budget_ms: int, cache_ttl: int, access: array{role: string}, http: array{token: ?string, detail_token: ?string, liveness: bool, rate_limit: int}, environment: array{support_warning_days: int}, data: array{connection_warning_ms: int, messenger: array{pending_warning: int, oldest_warning_minutes: int, oldest_critical_minutes: int, failed_critical: int}, table_growth: array<string, int>}, scheduled_tasks: array{heartbeats: array<string, string>, warning_beats: int, critical_beats: int}} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('tmonier_sylius_platform_health.time_budget_ms', $config['time_budget_ms']);
        $container->setParameter('tmonier_sylius_platform_health.cache_ttl', $config['cache_ttl']);
        $container->setParameter('tmonier_sylius_platform_health.access.role', $config['access']['role']);
        foreach ($config['http'] as $name => $value) {
            $container->setParameter('tmonier_sylius_platform_health.http.' . $name, $value);
        }
        $container->setParameter('tmonier_sylius_platform_health.environment.support_warning_days', $config['environment']['support_warning_days']);
        $container->setParameter('tmonier_sylius_platform_health.data.connection_warning_ms', $config['data']['connection_warning_ms']);
        $container->setParameter('tmonier_sylius_platform_health.data.table_growth', $config['data']['table_growth']);
        foreach ($config['data']['messenger'] as $name => $value) {
            $container->setParameter('tmonier_sylius_platform_health.data.messenger.' . $name, $value);
        }
        $container->setParameter('tmonier_sylius_platform_health.scheduled_tasks.heartbeats', array_map(static fn (string $beat): int => (int) BeatInterval::seconds($beat), $config['scheduled_tasks']['heartbeats']));
        $container->setParameter('tmonier_sylius_platform_health.scheduled_tasks.commands', array_map(strval(...), array_keys($config['scheduled_tasks']['heartbeats'])));
        $container->setParameter('tmonier_sylius_platform_health.scheduled_tasks.warning_beats', $config['scheduled_tasks']['warning_beats']);
        $container->setParameter('tmonier_sylius_platform_health.scheduled_tasks.critical_beats', $config['scheduled_tasks']['critical_beats']);
        $container->registerForAutoconfiguration(HealthCheckInterface::class)->addTag(HealthCheckInterface::TAG);

        (new PhpFileLoader($container, new FileLocator(__DIR__ . '/../../config')))->load('services.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        // The hooks of the page are part of the plugin: the application has nothing to import for them.
        /** @var array{sylius_twig_hooks: array<string, mixed>} $hooks */
        $hooks = Yaml::parseFile(__DIR__ . '/../../config/twig_hooks/admin.yaml');
        $container->prependExtensionConfig('sylius_twig_hooks', $hooks['sylius_twig_hooks']);

        // US-296: the heartbeat table, the first entity of the plugin, and the migration that creates it.
        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'TmonierSyliusPlatformHealthPlugin' => [
                        'type' => 'xml',
                        'dir' => 'config/doctrine',
                        'prefix' => 'Tmonier\\SyliusPlatformHealthPlugin\\Entity',
                        'is_bundle' => true,
                    ],
                ],
            ],
        ]);
        $this->prependDoctrineMigrations($container);
    }

    public function getAlias(): string
    {
        return 'tmonier_sylius_platform_health';
    }

    protected function getMigrationsNamespace(): string
    {
        return 'Tmonier\\SyliusPlatformHealthPlugin\\Migrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@TmonierSyliusPlatformHealthPlugin/src/Migrations';
    }

    /**
     * @return list<string>
     */
    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return ['Sylius\\Bundle\\CoreBundle\\Migrations'];
    }
}
