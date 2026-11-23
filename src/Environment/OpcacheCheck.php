<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final readonly class OpcacheCheck implements HealthCheckInterface
{
    public function __construct(
        private OpcacheStateReaderInterface $reader,
    ) {
    }

    public function identifier(): string
    {
        return 'environment.opcache';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Environment;
    }

    public function run(): HealthResult
    {
        return match ($this->reader->read()) {
            OpcacheState::Active => HealthResult::ok(new TranslatableMessage('tmonier_platform_health.environment.opcache.ok')),
            OpcacheState::Inactive => HealthResult::warning(new TranslatableMessage('tmonier_platform_health.environment.opcache.inactive'), null, DocumentationLink::create()),
            OpcacheState::Restricted => HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.environment.opcache.restricted'), null, DocumentationLink::create()),
        };
    }
}
