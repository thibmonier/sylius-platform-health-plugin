<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

/**
 * Reads kernel.debug and kernel.environment, never an environment variable: only "prod", "dev", "test" (or
 * "other") and a boolean reach the screen.
 */
final readonly class AppDebugCheck implements HealthCheckInterface
{
    private const KNOWN_ENVIRONMENTS = ['prod', 'dev', 'test'];

    public function __construct(
        private bool $debug,
        private string $environment,
    ) {
    }

    public function identifier(): string
    {
        return 'environment.app_debug';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Environment;
    }

    public function run(): HealthResult
    {
        if ('prod' !== $this->environment) {
            return HealthResult::warning(
                new TranslatableMessage('tmonier_platform_health.environment.app_debug.not_prod', ['%environment%' => $this->displayableEnvironment()]),
                null,
                DocumentationLink::create(),
            );
        }

        if ($this->debug) {
            return HealthResult::critical(new TranslatableMessage('tmonier_platform_health.environment.app_debug.debug_in_prod'), null, DocumentationLink::create());
        }

        return HealthResult::ok(new TranslatableMessage('tmonier_platform_health.environment.app_debug.ok'));
    }

    private function displayableEnvironment(): string
    {
        return \in_array($this->environment, self::KNOWN_ENVIRONMENTS, true) ? $this->environment : 'other';
    }
}
