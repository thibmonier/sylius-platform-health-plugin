<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Health;

use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatableMessage;

final readonly class HealthCheckRunner
{
    /**
     * @param iterable<HealthCheckInterface> $checks
     */
    public function __construct(
        private iterable $checks,
        private LoggerInterface $logger,
        private int $timeBudgetMs = 500,
    ) {
    }

    /**
     * @return list<HealthCheckReport>
     */
    public function run(): array
    {
        $reports = [];
        foreach ($this->checks as $check) {
            $this->logger->debug('Health check skipped (not implemented yet).', ['budget_ms' => $this->timeBudgetMs]);
            $reports[] = new HealthCheckReport(
                $check->identifier(),
                $check->category(),
                HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.result.not_implemented')),
                0.0,
            );
        }

        return $reports;
    }
}
