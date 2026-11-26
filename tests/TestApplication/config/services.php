<?php

declare(strict_types=1);

use Symfony\Component\Clock\MockClock;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\FixedMigrationStatusReader;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\BeatCommand;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\DemoDataCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\DemoEnvironmentCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\DemoShopCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\FailingCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\FailingClock;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\UnmeasurableCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\SupportAssessor;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthSnapshotProvider;

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

    // The snapshot provider is final: a test makes it throw through its clock (FailingClock), which is read after the probes ran.
    $services->set('tmonier_sylius_platform_health.test.failing_clock', FailingClock::class);
    $services->set('tmonier_sylius_platform_health.snapshot_provider', HealthSnapshotProvider::class)
        ->args([
            service('tmonier_sylius_platform_health.runner'),
            service('cache.app'),
            service('tmonier_sylius_platform_health.test.failing_clock'),
            service('logger'),
            param('tmonier_sylius_platform_health.cache_ttl'),
        ])
    ;

    // The test database is built from the mapping, not from the migrations: every Sylius migration would read as pending and
    // turn the data category critical on every page test. The real reader is tested on its own (DataChecksTest).
    $services->set('tmonier_sylius_platform_health.data.migration_status_reader', FixedMigrationStatusReader::class);

    $services->alias('tmonier_sylius_platform_health.test.runner', 'tmonier_sylius_platform_health.runner')->public();

    // US-294: the readers of the data probes, reached by the integration tests against the database of the test application.
    foreach (['connection_pinger', 'schema_drift_reader', 'table_row_estimator'] as $reader) {
        $services->alias('tmonier_sylius_platform_health.test.data.' . $reader, 'tmonier_sylius_platform_health.data.' . $reader)->public();
    }
    $services->alias('tmonier_sylius_platform_health.test.migrations_dependency_factory', 'doctrine.migrations.dependency_factory')->public();
    // US-295: the queues as the probes read them, and a Doctrine transport of Sylius to put a message in.
    $services->alias('tmonier_sylius_platform_health.test.data.messenger_queues', 'tmonier_sylius_platform_health.data.messenger_queues')->public();
    $services->alias('tmonier_sylius_platform_health.test.transport.main', 'messenger.transport.main')->public();
    // US-296: a declared command to run, and the store of its heartbeat.
    $services->set(BeatCommand::class)->autoconfigure();
    $services->alias('tmonier_sylius_platform_health.test.heartbeat_store', 'tmonier_sylius_platform_health.scheduled_tasks.heartbeat_store')->public();
};
