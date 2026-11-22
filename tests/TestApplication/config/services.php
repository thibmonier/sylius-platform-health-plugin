<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\DemoDataCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\DemoEnvironmentCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\DemoShopCheck;

/*
 * Test application only: three probes declared like an integrator would, as plain services. Nothing tags them by hand,
 * the autoconfiguration of HealthCheckInterface does.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    foreach ([DemoEnvironmentCheck::class, DemoDataCheck::class, DemoShopCheck::class] as $check) {
        $services->set($check)->autoconfigure();
    }

    $services->alias('tmonier_sylius_platform_health.test.runner', 'tmonier_sylius_platform_health.runner')->public();
};
