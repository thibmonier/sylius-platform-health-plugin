<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Health;

use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Stub: runs the probes at every call, without sharing anything and without honouring the refresh flag.
 */
final readonly class HealthSnapshotProvider
{
    public function __construct(
        private HealthCheckRunner $runner,
        private CacheInterface $cache,
        private ClockInterface $clock,
        private LoggerInterface $logger,
        private int $ttlSeconds = 60,
    ) {
    }

    public function snapshot(bool $refresh = false): HealthSnapshot
    {
        $this->logger->debug('The health snapshot is not cached yet.', ['refresh' => $refresh, 'ttl' => $this->ttlSeconds, 'cache' => $this->cache::class]);

        return new HealthSnapshot($this->runner->run(), $this->clock->now());
    }
}
