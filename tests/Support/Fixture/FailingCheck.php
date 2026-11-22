<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture;

use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final class FailingCheck implements HealthCheckInterface
{
    public const SECRET = 'SQLSTATE[HY000] mysql://root:hunter2@db.internal:3306/shop';

    public function identifier(): string
    {
        return 'plugins.failing';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Plugins;
    }

    public function run(): HealthResult
    {
        ProbeExecutions::record();

        throw new \RuntimeException(self::SECRET);
    }
}
