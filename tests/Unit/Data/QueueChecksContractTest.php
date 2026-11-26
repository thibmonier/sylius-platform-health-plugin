<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Data;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tmonier\SyliusPlatformHealthPlugin\Data\MessengerFailedCheck;
use Tmonier\SyliusPlatformHealthPlugin\Data\MessengerOldestCheck;
use Tmonier\SyliusPlatformHealthPlugin\Data\MessengerPendingCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\CachePoolCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\WritableDirsCheck;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;

/**
 * US-295, decision of Thibaut of 2026-11-25: Messenger probes in data, cache and directories in environment.
 */
final class QueueChecksContractTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<HealthCheckInterface>, string, HealthCategory}>
     */
    public static function checks(): iterable
    {
        yield 'pending' => [MessengerPendingCheck::class, 'data.messenger_pending', HealthCategory::Data];
        yield 'oldest' => [MessengerOldestCheck::class, 'data.messenger_oldest', HealthCategory::Data];
        yield 'failed' => [MessengerFailedCheck::class, 'data.messenger_failed', HealthCategory::Data];
        yield 'cache' => [CachePoolCheck::class, 'environment.cache_pool', HealthCategory::Environment];
        yield 'directories' => [WritableDirsCheck::class, 'environment.writable_dirs', HealthCategory::Environment];
    }

    /**
     * @param class-string<HealthCheckInterface> $class
     */
    #[DataProvider('checks')]
    public function testTheIdentifierIsAConstantOfItsCategory(string $class, string $identifier, HealthCategory $category): void
    {
        $check = (new \ReflectionClass($class))->newInstanceWithoutConstructor();

        self::assertSame($identifier, $check->identifier());
        self::assertSame($category, $check->category());
    }
}
