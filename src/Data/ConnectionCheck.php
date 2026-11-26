<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final readonly class ConnectionCheck implements HealthCheckInterface
{
    public function __construct(
        private ConnectionPingerInterface $pinger,
        private ClockInterface $clock,
        private LoggerInterface $logger,
        private int $warningMs = 500,
    ) {
    }

    public function identifier(): string
    {
        return 'data.connection';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Data;
    }

    public function run(): HealthResult
    {
        $start = $this->clock->now();

        try {
            $this->pinger->ping();
        } catch (\Throwable $exception) {
            // The message of a driver exception may carry the DSN, the host or the user: the log only, never the screen.
            $this->logger->error('The database did not answer the health check.', ['exception' => $exception]);

            return HealthResult::critical(new TranslatableMessage('tmonier_platform_health.data.connection.unreachable'), null, DocumentationLink::create());
        }

        $durationMs = $this->elapsedMs($start);
        if ($durationMs > $this->warningMs) {
            return HealthResult::warning(new TranslatableMessage('tmonier_platform_health.data.connection.slow', ['%duration_ms%' => $durationMs, '%threshold_ms%' => $this->warningMs]), null, DocumentationLink::create());
        }

        return HealthResult::ok(new TranslatableMessage('tmonier_platform_health.data.connection.ok', ['%duration_ms%' => $durationMs]));
    }

    private function elapsedMs(\DateTimeImmutable $start): int
    {
        $now = $this->clock->now();
        $microseconds = ((int) $now->format('U') - (int) $start->format('U')) * 1_000_000 + (int) $now->format('u') - (int) $start->format('u');

        return intdiv($microseconds, 1000);
    }
}
