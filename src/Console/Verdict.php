<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Console;

use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

/**
 * What the retained checks add up to: the worst status, and the exit code a script reads.
 * 0 all "ok", 1 a "warning" or an "unknown" (a check that does not measure does not pass a gate in silence), 2 a "critical".
 */
final readonly class Verdict
{
    public const EXIT_OK = 0;

    public const EXIT_DEGRADED = 1;

    public const EXIT_CRITICAL = 2;

    public const EXIT_INVALID_OPTION = 3;

    /**
     * @param list<HealthCheckReport> $reports
     */
    public function __construct(
        private array $reports,
    ) {
    }

    public function status(): HealthStatus
    {
        foreach ([HealthStatus::Critical, HealthStatus::Warning, HealthStatus::Unknown] as $status) {
            if ($this->count($status) > 0) {
                return $status;
            }
        }

        return HealthStatus::Ok;
    }

    public function exitCode(): int
    {
        return match ($this->status()) {
            HealthStatus::Ok => self::EXIT_OK,
            HealthStatus::Warning, HealthStatus::Unknown => self::EXIT_DEGRADED,
            HealthStatus::Critical => self::EXIT_CRITICAL,
        };
    }

    public function count(HealthStatus $status): int
    {
        return \count(array_filter($this->reports, static fn (HealthCheckReport $report): bool => $report->result->status === $status));
    }

    public function total(): int
    {
        return \count($this->reports);
    }
}
