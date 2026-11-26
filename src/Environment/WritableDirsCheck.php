<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final readonly class WritableDirsCheck implements HealthCheckInterface
{
    /**
     * @param array<string, string> $directories absolute path of each directory, keyed by the relative label shown
     */
    public function __construct(
        private array $directories,
    ) {
    }

    public function identifier(): string
    {
        return 'environment.writable_dirs';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Environment;
    }

    public function run(): HealthResult
    {
        // Red stub (T-295-01): reads its collaborators and answers nothing yet; the green commit replaces it.
        return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.result.check_failed', ['%count%' => \count($this->directories)]));
    }
}
