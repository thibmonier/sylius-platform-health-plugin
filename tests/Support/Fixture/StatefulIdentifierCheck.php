<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

/**
 * Wrong on purpose: the identifier comes from the constructor, which the compilation does not call.
 */
final readonly class StatefulIdentifierCheck implements HealthCheckInterface
{
    public function __construct(private string $identifier)
    {
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Plugins;
    }

    public function run(): HealthResult
    {
        return HealthResult::ok(new TranslatableMessage('tests.fixture.stateful'));
    }
}
