<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final class UnmeasurableCheck implements HealthCheckInterface
{
    public function identifier(): string
    {
        return 'plugins.demo';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Plugins;
    }

    public function run(): HealthResult
    {
        ProbeExecutions::record();

        return HealthResult::unknown(new TranslatableMessage('Demo probe: the extension is not installed, nothing to measure.'));
    }
}
