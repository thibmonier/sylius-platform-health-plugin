<?php

declare(strict_types=1);

use Symfony\Component\Clock\MockClock;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\DemoDataCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\DemoEnvironmentCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\DemoShopCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\FailingCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\UnmeasurableCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\SupportAssessor;

/*
 * Test application only: five probes declared like an integrator would, as plain services. Nothing tags them by hand,
 * the autoconfiguration of HealthCheckInterface does.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    foreach ([DemoEnvironmentCheck::class, DemoDataCheck::class, DemoShopCheck::class, UnmeasurableCheck::class, FailingCheck::class] as $check) {
        $services->set($check)->autoconfigure();
    }

    // The environment probes of the plugin read the date of the day through the clock service: fixed here, so that the
    // test suite does not change when a PHP branch leaves support.
    $services->set('tmonier_sylius_platform_health.test.fixed_clock', MockClock::class)->args(['2026-11-23 10:00:00', 'UTC']);
    $services->set('tmonier_sylius_platform_health.environment.support_assessor', SupportAssessor::class)
        ->args([
            service('tmonier_sylius_platform_health.test.fixed_clock'),
            service('tmonier_sylius_platform_health.environment.end_of_support_table'),
            180,
        ])
    ;

    $services->alias('tmonier_sylius_platform_health.test.runner', 'tmonier_sylius_platform_health.runner')->public();
};
