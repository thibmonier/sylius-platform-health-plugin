<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Health;

use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Runs the probes behind a short cache, so that three administrators looking at the page at the same time
 * trigger one run, not three. An explicit refresh ignores the cache and replaces it.
 */
final readonly class HealthSnapshotProvider
{
    private const CACHE_KEY = 'tmonier_platform_health.snapshot';

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
        try {
            if ($refresh) {
                $this->cache->delete(self::CACHE_KEY);
            }

            return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): HealthSnapshot {
                $item->expiresAfter($this->ttlSeconds);

                return $this->measure();
            });
        } catch (\Throwable $exception) {
            // A cache that cannot store the result (unavailable pool, probe parameter that cannot be serialised) must not break the page.
            $this->logger->warning('The health snapshot cache could not be used.', ['exception' => $exception]);

            return $this->measure();
        }
    }

    private function measure(): HealthSnapshot
    {
        return new HealthSnapshot($this->runner->run(), $this->clock->now());
    }
}
