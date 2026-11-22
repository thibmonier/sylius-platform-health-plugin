<?php

declare(strict_types=1);

use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthSnapshotProvider;
use Tmonier\SyliusPlatformHealthPlugin\Menu\AdminMenuListener;
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthSummaryBuilder;
use Tmonier\SyliusPlatformHealthPlugin\Twig\ActionUrlExtension;
use Tmonier\SyliusPlatformHealthPlugin\Twig\Component\HealthComponent;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('tmonier_sylius_platform_health.runner', HealthCheckRunner::class)
        ->args([
            tagged_iterator(HealthCheckInterface::TAG),
            service('logger'),
            param('tmonier_sylius_platform_health.time_budget_ms'),
        ])
    ;

    $services->alias(HealthCheckRunner::class, 'tmonier_sylius_platform_health.runner');

    $services->set('tmonier_sylius_platform_health.snapshot_provider', HealthSnapshotProvider::class)
        ->args([
            service('tmonier_sylius_platform_health.runner'),
            service('cache.app'),
            service('clock'),
            service('logger'),
            param('tmonier_sylius_platform_health.cache_ttl'),
        ])
    ;

    $services->set('tmonier_sylius_platform_health.summary_builder', HealthSummaryBuilder::class);

    $services->set('tmonier_sylius_platform_health.twig.component.health', HealthComponent::class)
        ->args([
            service('tmonier_sylius_platform_health.snapshot_provider'),
            service('tmonier_sylius_platform_health.summary_builder'),
        ])
        ->tag('sylius.live_component.admin', [
            'key' => 'tmonier_platform_health:summary',
            'template' => '@TmonierSyliusPlatformHealthPlugin/admin/summary.html.twig',
        ])
    ;

    $services->set('tmonier_sylius_platform_health.twig.action_url', ActionUrlExtension::class)
        ->args([service('router'), service('logger')])
        ->tag('twig.extension')
    ;

    $services->set('tmonier_sylius_platform_health.menu.admin', AdminMenuListener::class)
        ->tag('kernel.event_listener', ['event' => 'sylius.menu.admin.main'])
    ;
};
