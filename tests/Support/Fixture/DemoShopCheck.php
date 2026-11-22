<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final class DemoShopCheck implements HealthCheckInterface
{
    public function identifier(): string
    {
        return 'shop.demo';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Shop;
    }

    public function run(): HealthResult
    {
        return HealthResult::critical(new TranslatableMessage('tests.fixture.shop.demo'));
    }
}
