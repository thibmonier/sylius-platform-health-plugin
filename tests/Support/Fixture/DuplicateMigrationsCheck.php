<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final class DuplicateMigrationsCheck implements HealthCheckInterface
{
    public function identifier(): string
    {
        return 'data.migrations';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Data;
    }

    public function run(): HealthResult
    {
        return HealthResult::ok(new TranslatableMessage('tests.fixture.data.migrations'));
    }
}
