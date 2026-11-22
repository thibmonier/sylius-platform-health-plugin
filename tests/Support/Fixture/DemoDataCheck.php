<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final class DemoDataCheck implements HealthCheckInterface
{
    public function identifier(): string
    {
        return 'data.demo';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Data;
    }

    public function run(): HealthResult
    {
        ProbeExecutions::record();

        return HealthResult::warning(new TranslatableMessage('Demo probe: 7 products are enabled without a price on the B2B channel.'));
    }
}
