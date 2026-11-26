<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\Health;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

/**
 * The probe of the demonstration plugin. Loaded only when the health plugin is installed: this class implements its interface.
 */
final class OutboxCheck implements HealthCheckInterface
{
    public function identifier(): string
    {
        return 'demo_webhooks.outbox';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Plugins;
    }

    public function run(): HealthResult
    {
        return HealthResult::ok(new TranslatableMessage('Demo webhooks: the outbox is empty.'));
    }
}
