<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

/**
 * Red stub (US-293).
 */
final class OpcacheCheck implements HealthCheckInterface
{
    public function __construct(
        public readonly OpcacheStateReaderInterface $reader,
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
        return HealthResult::unknown(new TranslatableMessage('stub'));
    }
}
