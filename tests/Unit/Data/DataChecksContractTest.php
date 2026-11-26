<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Data;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tmonier\SyliusPlatformHealthPlugin\Data\ConnectionCheck;
use Tmonier\SyliusPlatformHealthPlugin\Data\PendingMigrationsCheck;
use Tmonier\SyliusPlatformHealthPlugin\Data\SchemaDriftCheck;
use Tmonier\SyliusPlatformHealthPlugin\Data\TableGrowthCheck;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;

final class DataChecksContractTest extends TestCase
{
    /**
     * @return iterable<string, array{class-string<HealthCheckInterface>, string}>
     */
    public static function checks(): iterable
    {
        yield 'connection' => [ConnectionCheck::class, 'data.connection'];
        yield 'migrations' => [PendingMigrationsCheck::class, 'data.pending_migrations'];
        yield 'drift' => [SchemaDriftCheck::class, 'data.schema_drift'];
        yield 'growth' => [TableGrowthCheck::class, 'data.table_growth'];
    }

    /**
     * @param class-string<HealthCheckInterface> $class
     */
    #[DataProvider('checks')]
    public function testTheIdentifierIsAConstantOfTheDataCategory(string $class, string $identifier): void
    {
        $check = (new \ReflectionClass($class))->newInstanceWithoutConstructor();

        self::assertSame($identifier, $check->identifier());
        self::assertSame(HealthCategory::Data, $check->category());
    }
}
