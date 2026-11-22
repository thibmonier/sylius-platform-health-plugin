<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final class DemoEnvironmentCheck implements HealthCheckInterface
{
    public function identifier(): string
    {
        return 'environment.demo';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Environment;
    }

    public function run(): HealthResult
    {
        ProbeExecutions::record();

        return HealthResult::ok(new TranslatableMessage('Demo probe: the PHP version is supported.'));
    }
}
