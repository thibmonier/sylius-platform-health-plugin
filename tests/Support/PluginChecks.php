<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support;

/**
 * The probes the plugin ships, so a test can keep only those of the application.
 */
final class PluginChecks
{
    public const IDENTIFIERS = [
        'environment.php_version',
        'environment.symfony_version',
        'environment.sylius_version',
        'environment.php_extensions',
        'environment.opcache',
        'environment.app_debug',
        'data.connection',
        'data.pending_migrations',
        'data.schema_drift',
        'data.table_growth',
        'data.messenger_pending',
        'data.messenger_oldest',
        'data.messenger_failed',
        'environment.cache_pool',
        'environment.writable_dirs',
        'scheduled_tasks.heartbeat',
    ];

    public static function ships(string $identifier): bool
    {
        return \in_array($identifier, self::IDENTIFIERS, true);
    }
}
