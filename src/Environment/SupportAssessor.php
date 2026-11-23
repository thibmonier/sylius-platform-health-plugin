<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

/**
 * Red stub (US-293).
 */
final class SupportAssessor
{
    public function __construct(
        public readonly ClockInterface $clock,
        public readonly EndOfSupportTable $table,
        public readonly int $warningDays = 180,
    ) {
    }

    public function assessTable(string $name, string $version, ?\DateTimeImmutable $end): HealthResult
    {
        return HealthResult::unknown(new TranslatableMessage('stub'));
    }

    public function assessKernel(string $name, string $version, \DateTimeImmutable $maintenanceEnd, \DateTimeImmutable $lifeEnd): HealthResult
    {
        return HealthResult::unknown(new TranslatableMessage('stub'));
    }
}
