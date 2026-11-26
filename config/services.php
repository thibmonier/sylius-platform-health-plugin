<?php

declare(strict_types=1);

use Symfony\Component\HttpKernel\Kernel;
use Tmonier\SyliusPlatformHealthPlugin\Data\ConnectionCheck;
use Tmonier\SyliusPlatformHealthPlugin\Data\DbalConnectionPinger;
use Tmonier\SyliusPlatformHealthPlugin\Data\DbalTableRowEstimator;
use Tmonier\SyliusPlatformHealthPlugin\Data\DoctrineMigrationStatusReader;
use Tmonier\SyliusPlatformHealthPlugin\Data\OrmSchemaDriftReader;
use Tmonier\SyliusPlatformHealthPlugin\Data\PendingMigrationsCheck;
use Tmonier\SyliusPlatformHealthPlugin\Data\SchemaDriftCheck;
use Tmonier\SyliusPlatformHealthPlugin\Data\TableGrowthCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\AppDebugCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\EndOfSupportTable;
use Tmonier\SyliusPlatformHealthPlugin\Environment\NativeOpcacheStateReader;
use Tmonier\SyliusPlatformHealthPlugin\Environment\OpcacheCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\PhpExtensionsCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\PhpVersionCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\SupportAssessor;
use Tmonier\SyliusPlatformHealthPlugin\Environment\SyliusVersionCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\SymfonyVersionCheck;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthSnapshotProvider;
use Tmonier\SyliusPlatformHealthPlugin\Menu\AdminMenuListener;
use Tmonier\SyliusPlatformHealthPlugin\Controller\HealthPageController;
use Tmonier\SyliusPlatformHealthPlugin\Security\PlatformHealthVoter;
use Tmonier\SyliusPlatformHealthPlugin\Security\SensitiveValueMasker;
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthSummaryBuilder;
use Tmonier\SyliusPlatformHealthPlugin\Twig\ActionUrlExtension;
use Tmonier\SyliusPlatformHealthPlugin\Twig\Component\HealthComponent;
use Tmonier\SyliusPlatformHealthPlugin\Twig\Component\HealthDashboardComponent;
use Tmonier\SyliusPlatformHealthPlugin\Twig\SafeTextExtension;

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

    $services->set('tmonier_sylius_platform_health.twig.component.dashboard', HealthDashboardComponent::class)
        ->args([
            service('tmonier_sylius_platform_health.snapshot_provider'),
            service('tmonier_sylius_platform_health.summary_builder'),
            service('logger'),
        ])
        ->tag('sylius.live_component.admin', [
            'key' => 'tmonier_platform_health:dashboard',
            'template' => '@TmonierSyliusPlatformHealthPlugin/admin/dashboard.html.twig',
        ])
    ;

    $services->set('tmonier_sylius_platform_health.twig.action_url', ActionUrlExtension::class)
        ->args([service('router'), service('logger')])
        ->tag('twig.extension')
    ;

    $services->set('tmonier_sylius_platform_health.twig.safe_text', SafeTextExtension::class)
        ->args([
            service('translator'),
            service('tmonier_sylius_platform_health.security.masker'),
            service('logger'),
        ])
        ->tag('twig.extension')
    ;

    $services->set('tmonier_sylius_platform_health.security.masker', SensitiveValueMasker::class)
        ->args([param('kernel.project_dir')])
    ;

    $services->set('tmonier_sylius_platform_health.security.voter', PlatformHealthVoter::class)
        ->args([service('security.role_hierarchy'), param('tmonier_sylius_platform_health.access.role')])
        ->tag('security.voter')
    ;

    $services->set('tmonier_sylius_platform_health.controller.page', HealthPageController::class)
        ->args([service('twig')])
        ->tag('controller.service_arguments')
    ;

    $services->set('tmonier_sylius_platform_health.menu.admin', AdminMenuListener::class)
        ->args([service('security.authorization_checker')])
        ->tag('kernel.event_listener', ['event' => 'sylius.menu.admin.main'])
    ;

    // Environment probes (US-293): autoconfigured like any other probe, nothing to declare in the application.
    $services->set('tmonier_sylius_platform_health.environment.end_of_support_table', EndOfSupportTable::class);

    $services->set('tmonier_sylius_platform_health.environment.support_assessor', SupportAssessor::class)
        ->args([
            service('clock'),
            service('tmonier_sylius_platform_health.environment.end_of_support_table'),
            param('tmonier_sylius_platform_health.environment.support_warning_days'),
        ])
    ;

    $services->set('tmonier_sylius_platform_health.environment.php_version', PhpVersionCheck::class)
        ->args([
            service('tmonier_sylius_platform_health.environment.support_assessor'),
            service('tmonier_sylius_platform_health.environment.end_of_support_table'),
        ])
        ->autoconfigure()
    ;

    $services->set('tmonier_sylius_platform_health.environment.sylius_version', SyliusVersionCheck::class)
        ->args([
            service('tmonier_sylius_platform_health.environment.support_assessor'),
            service('tmonier_sylius_platform_health.environment.end_of_support_table'),
        ])
        ->autoconfigure()
    ;

    $services->set('tmonier_sylius_platform_health.environment.symfony_version', SymfonyVersionCheck::class)
        ->args([
            service('tmonier_sylius_platform_health.environment.support_assessor'),
            Kernel::VERSION,
            Kernel::END_OF_MAINTENANCE,
            Kernel::END_OF_LIFE,
        ])
        ->autoconfigure()
    ;

    $services->set('tmonier_sylius_platform_health.environment.php_extensions', PhpExtensionsCheck::class)
        ->autoconfigure()
    ;

    $services->set('tmonier_sylius_platform_health.environment.opcache_reader', NativeOpcacheStateReader::class);

    $services->set('tmonier_sylius_platform_health.environment.opcache', OpcacheCheck::class)
        ->args([service('tmonier_sylius_platform_health.environment.opcache_reader')])
        ->autoconfigure()
    ;

    $services->set('tmonier_sylius_platform_health.environment.app_debug', AppDebugCheck::class)
        ->args([param('kernel.debug'), param('kernel.environment')])
        ->autoconfigure()
    ;

    // Data probes (US-294): the default Doctrine connection only; read-only, nothing to declare in the application.
    $services->set('tmonier_sylius_platform_health.data.connection_pinger', DbalConnectionPinger::class)
        ->args([service('doctrine.dbal.default_connection')])
    ;

    $services->set('tmonier_sylius_platform_health.data.connection', ConnectionCheck::class)
        ->args([
            service('tmonier_sylius_platform_health.data.connection_pinger'),
            service('clock'),
            service('logger'),
            param('tmonier_sylius_platform_health.data.connection_warning_ms'),
        ])
        ->autoconfigure()
    ;

    $services->set('tmonier_sylius_platform_health.data.migration_status_reader', DoctrineMigrationStatusReader::class)
        ->args([service('doctrine.migrations.dependency_factory')->nullOnInvalid()])
    ;

    $services->set('tmonier_sylius_platform_health.data.pending_migrations', PendingMigrationsCheck::class)
        ->args([service('tmonier_sylius_platform_health.data.migration_status_reader')])
        ->autoconfigure()
    ;

    $services->set('tmonier_sylius_platform_health.data.schema_drift_reader', OrmSchemaDriftReader::class)
        ->args([service('doctrine.orm.default_entity_manager')->nullOnInvalid()])
    ;

    $services->set('tmonier_sylius_platform_health.data.schema_drift', SchemaDriftCheck::class)
        ->args([service('tmonier_sylius_platform_health.data.schema_drift_reader')])
        ->autoconfigure()
    ;

    $services->set('tmonier_sylius_platform_health.data.table_row_estimator', DbalTableRowEstimator::class)
        ->args([service('doctrine.dbal.default_connection')])
    ;

    $services->set('tmonier_sylius_platform_health.data.table_growth', TableGrowthCheck::class)
        ->args([
            service('tmonier_sylius_platform_health.data.table_row_estimator'),
            param('tmonier_sylius_platform_health.data.table_growth'),
        ])
        ->autoconfigure()
    ;
};
