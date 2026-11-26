<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatableMessage;
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
        // Red stub (T-294-01): reads its collaborators and answers nothing yet; the green commit replaces it.
        try {
            $this->pinger->ping();
        } catch (\Throwable) {
        }
        $this->logger->debug('Stub.', ['now' => $this->clock->now(), 'threshold_ms' => $this->warningMs]);

        return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.result.check_failed'));
    }
}
