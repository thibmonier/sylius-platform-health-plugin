<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

/**
 * Symfony publishes its own dates (Kernel::END_OF_MAINTENANCE and Kernel::END_OF_LIFE, as "MM/YYYY"): no table.
 */
final readonly class SymfonyVersionCheck implements HealthCheckInterface
{
    public function __construct(
        private SupportAssessor $assessor,
        private string $version,
        private string $endOfMaintenance,
        private string $endOfLife,
    ) {
    }

    public function identifier(): string
    {
        return 'environment.symfony_version';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Environment;
    }

    public function run(): HealthResult
    {
        $maintenance = $this->lastDayOf($this->endOfMaintenance);
        $life = $this->lastDayOf($this->endOfLife);
        if (!$maintenance instanceof \DateTimeImmutable || !$life instanceof \DateTimeImmutable) {
            return $this->assessor->unreadable('Symfony');
        }

        return $this->assessor->assessKernel('Symfony', $this->version, $maintenance, $life);
    }

    private function lastDayOf(string $monthYear): ?\DateTimeImmutable
    {
        if (1 !== preg_match('#^(0[1-9]|1[0-2])/(\d{4})$#', $monthYear, $matches)) {
            return null;
        }

        return (new \DateTimeImmutable(\sprintf('%s-%s-01', $matches[2], $matches[1]), new \DateTimeZone('UTC')))->modify('last day of this month');
    }
}
