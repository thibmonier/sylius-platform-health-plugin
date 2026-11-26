<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\DependencyInjection\DemoWebhooksExtension;

/**
 * A plugin that the health plugin monitors without requiring it (US-303): it stands for webhook-outbox, and the test
 * application enables it like any plugin. The check "does the health plugin exist?" is injectable, so that a test can
 * build the bundle as if the health plugin were not installed.
 */
final class DemoWebhooksBundle extends Bundle
{
    /**
     * @param (\Closure(string): bool)|null $healthPluginExists answers "does this interface exist?"; interface_exists() by default
     */
    public function __construct(
        private readonly ?\Closure $healthPluginExists = null,
    ) {
    }

    protected function createContainerExtension(): ExtensionInterface
    {
        return new DemoWebhooksExtension($this->healthPluginExists);
    }
}
