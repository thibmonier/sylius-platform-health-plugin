<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tmonier\SyliusPlatformHealthPlugin\DependencyInjection\TmonierSyliusPlatformHealthExtension;
use Tmonier\SyliusPlatformHealthPlugin\TmonierSyliusPlatformHealthPlugin;

/**
 * A plain Symfony container with the plugin and the services it reads (logger, clock, cache, one SQLite connection):
 * no Sylius kernel, no database. Not compiled: a test adds what it needs, then compiles.
 */
trait BuildsHealthContainer
{
    protected function container(string ...$checkClasses): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new TmonierSyliusPlatformHealthExtension());
        $container->loadFromExtension('tmonier_sylius_platform_health');
        $container->register('logger', NullLogger::class);
        // The environment probes of the plugin read the date through the clock service and two kernel parameters.
        $container->register('clock', NativeClock::class);
        // The cache and directory probes read the application cache and three kernel paths.
        $container->register('cache.app', ArrayAdapter::class);
        $container->setParameter('kernel.project_dir', sys_get_temp_dir());
        $container->setParameter('kernel.cache_dir', sys_get_temp_dir());
        $container->setParameter('kernel.logs_dir', sys_get_temp_dir());
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'test');
        // The data probes read the default Doctrine connection: an in-memory SQLite one, opened only if a probe queries it.
        $container->register('doctrine.dbal.default_connection', Connection::class)
            ->setFactory([DriverManager::class, 'getConnection'])
            ->setArguments([['driver' => 'pdo_sqlite', 'memory' => true]])
        ;
        foreach ($checkClasses as $checkClass) {
            $container->register($checkClass, $checkClass)->setAutoconfigured(true);
        }
        $container->setAlias('test.runner', 'tmonier_sylius_platform_health.runner')->setPublic(true);
        (new TmonierSyliusPlatformHealthPlugin())->build($container);

        return $container;
    }
}
