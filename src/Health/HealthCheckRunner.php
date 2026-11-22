<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Health;

use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Runs every probe so that a faulty one can never break the caller: an exception is logged with its trace and
 * becomes "unknown", a probe slower than its time budget becomes "unknown", the other probes still answer.
 *
 * Depends on no Sylius class: pure Symfony and PSR, so a probe is testable without a Sylius kernel.
 */
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
            $reports[] = $this->execute($check);
        }

        return $reports;
    }

    private function execute(HealthCheckInterface $check): HealthCheckReport
    {
        $start = hrtime(true);

        try {
            $identifier = $check->identifier();
            $category = $check->category();
            $result = $check->run();
        } catch (\Throwable $exception) {
            return $this->failed($check, $exception, $this->elapsedMs($start));
        }

        $durationMs = $this->elapsedMs($start);

        return new HealthCheckReport($identifier, $category, $this->withinBudget($identifier, $result, $durationMs), $durationMs);
    }

    private function withinBudget(string $identifier, HealthResult $result, float $durationMs): HealthResult
    {
        if ($durationMs <= $this->timeBudgetMs) {
            return $result;
        }

        $this->logger->warning('Health check "{identifier}" exceeded its time budget.', [
            'identifier' => $identifier,
            'duration_ms' => $durationMs,
            'budget_ms' => $this->timeBudgetMs,
        ]);

        return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.result.budget_exceeded', ['%budget_ms%' => $this->timeBudgetMs]));
    }

    private function failed(HealthCheckInterface $check, \Throwable $exception, float $durationMs): HealthCheckReport
    {
        [$identifier, $category] = $this->identityOf($check);

        // The exception goes to the log only (PSR-3 "exception" key keeps the trace); the screen gets a generic summary.
        $this->logger->error('Health check "{identifier}" failed.', ['identifier' => $identifier, 'exception' => $exception]);

        return new HealthCheckReport(
            $identifier,
            $category,
            HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.result.check_failed')),
            $durationMs,
        );
    }

    /**
     * @return array{string, HealthCategory}
     */
    private function identityOf(HealthCheckInterface $check): array
    {
        try {
            return [$check->identifier(), $check->category()];
        } catch (\Throwable) {
            // The identity itself is broken: the compilation pass rules it out, this is the last line of defence.
            return [$check::class, HealthCategory::Plugins];
        }
    }

    private function elapsedMs(int $start): float
    {
        return (hrtime(true) - $start) / 1_000_000;
    }
}
