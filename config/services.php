<?php

declare(strict_types=1);

use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;

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
};
