<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\Health;

use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

/**
 * A probe of the demonstration plugin that fails: the runner must make it "unknown" and keep the other probes.
 */
final class BrokenCheck implements HealthCheckInterface
{
    public const MESSAGE = 'Demo webhooks: the outbox table cannot be read';

    public function identifier(): string
    {
        return 'demo_webhooks.broken';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Plugins;
    }

    public function run(): HealthResult
    {
        throw new \RuntimeException(self::MESSAGE);
    }
}
