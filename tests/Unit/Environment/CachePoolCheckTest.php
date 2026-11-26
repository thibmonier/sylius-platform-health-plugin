<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Environment;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Environment\CachePoolCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class CachePoolCheckTest extends TestCase
{
    use ResultAssertions;

    public function testACacheThatKeepsWhatItIsGivenIsOkAndTheWitnessKeyIsRemoved(): void
    {
        $pool = new ArrayAdapter();

        self::assertSummary((new CachePoolCheck($pool))->run(), HealthStatus::Ok, 'tmonier_platform_health.environment.cache_pool.ok');
        self::assertSame([], $pool->getValues(), 'The witness key is deleted after the round trip.');
    }

    public function testACacheThatLosesWhatItIsGivenIsCritical(): void
    {
        $result = (new CachePoolCheck(new NullAdapter()))->run();

        self::assertSummary($result, HealthStatus::Critical, 'tmonier_platform_health.environment.cache_pool.failed');
        self::assertEquals(DocumentationLink::create(), $result->action);
    }

    public function testACacheThatThrowsIsCriticalNeverAnException(): void
    {
        $pool = new class() extends ArrayAdapter {
            public function save(CacheItemInterface $item): bool
            {
                throw new \RuntimeException('redis://:secret@cache.internal:6379 is down');
            }
        };

        $result = (new CachePoolCheck($pool))->run();

        self::assertSummary($result, HealthStatus::Critical, 'tmonier_platform_health.environment.cache_pool.failed');
        self::assertStringNotContainsString('secret', self::serialized($result));
    }
}
