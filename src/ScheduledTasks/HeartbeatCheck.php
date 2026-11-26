<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\ScheduledTasks;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final readonly class HeartbeatCheck implements HealthCheckInterface
{
    /**
     * @param array<string, int> $heartbeats seconds between two runs of each declared command
     */
    public function __construct(
        private HeartbeatStoreInterface $store,
        private ClockInterface $clock,
        private LoggerInterface $logger,
        private array $heartbeats,
        private int $warningBeats = 2,
        private int $criticalBeats = 4,
    ) {
    }

    public function identifier(): string
    {
        return 'scheduled_tasks.heartbeat';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::ScheduledTasks;
    }

    public function run(): HealthResult
    {
        // Red stub (T-296-01): reads its collaborators and answers nothing yet; the green commit (T-296-03) replaces it.
        try {
            $this->store->all();
        } catch (\Throwable) {
        }
        $this->clock->now();
        $this->logger->debug('Heartbeat probe stub.', ['commands' => \count($this->heartbeats), 'beats' => $this->warningBeats + $this->criticalBeats]);

        return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.result.check_failed'));
    }
}
