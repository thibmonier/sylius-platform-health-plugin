<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final readonly class CachePoolCheck implements HealthCheckInterface
{
    public function __construct(
        private CacheItemPoolInterface $pool,
    ) {
    }

    public function identifier(): string
    {
        return 'environment.cache_pool';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Environment;
    }

    private const KEY = 'tmonier_platform_health.cache_probe';

    public function run(): HealthResult
    {
        try {
            $kept = $this->roundTrip();
        } catch (\Throwable) {
            // A cache exception may carry the DSN of the cache server: nothing of it reaches the screen.
            $kept = false;
        }

        return $kept
            ? HealthResult::ok(new TranslatableMessage('tmonier_platform_health.environment.cache_pool.ok'))
            : HealthResult::critical(new TranslatableMessage('tmonier_platform_health.environment.cache_pool.failed'), null, DocumentationLink::create());
    }

    private function roundTrip(): bool
    {
        $token = bin2hex(random_bytes(8));
        $saved = $this->pool->save($this->pool->getItem(self::KEY)->set($token)->expiresAfter(60));
        $read = $this->pool->getItem(self::KEY);
        $this->pool->deleteItem(self::KEY);

        return $saved && $read->isHit() && $token === $read->get();
    }
}
