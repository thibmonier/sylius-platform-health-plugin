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
final class SyliusVersionCheck implements HealthCheckInterface
{
    public function __construct(
        public readonly SupportAssessor $assessor,
        public readonly EndOfSupportTable $table,
        public readonly ?string $version = null,
    ) {
    }

    public function identifier(): string
    {
        return 'environment.sylius_version';
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
