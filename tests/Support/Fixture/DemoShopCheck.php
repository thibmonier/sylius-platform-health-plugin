<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthActionLink;
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
        ProbeExecutions::record();

        return HealthResult::critical(new TranslatableMessage('Demo probe: no payment method is enabled on the B2B channel.'), action: new HealthActionLink('sylius_admin_payment_method_index', ['page' => 1]));
    }
}
