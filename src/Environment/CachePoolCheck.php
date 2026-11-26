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

    public function run(): HealthResult
    {
        // Red stub (T-295-01): reads its collaborators and answers nothing yet; the green commit replaces it.
        $this->pool->hasItem('tmonier_platform_health.cache_probe');

        return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.result.check_failed'));
    }
}
