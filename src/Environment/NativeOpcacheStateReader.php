<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

/**
 * Reads OPcache for the SAPI that runs the page (PHP-FPM or FrankenPHP, not the CLI). With `opcache.restrict_api`
 * the status function refuses the script: the state is then not measurable, which is not the same as inactive.
 */
final class NativeOpcacheStateReader implements OpcacheStateReaderInterface
{
    public function read(): OpcacheState
    {
        if (!\function_exists('opcache_get_status')) {
            return OpcacheState::Inactive;
        }

        $status = @opcache_get_status(false);
        if (\is_array($status)) {
            return true === ($status['opcache_enabled'] ?? false) ? OpcacheState::Active : OpcacheState::Inactive;
        }

        return $this->apiIsRestricted() ? OpcacheState::Restricted : OpcacheState::Inactive;
    }

    private function apiIsRestricted(): bool
    {
        $restriction = ini_get('opcache.restrict_api');

        return \is_string($restriction) && '' !== $restriction;
    }
}
