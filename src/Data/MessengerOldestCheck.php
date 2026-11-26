<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Psr\Clock\ClockInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final readonly class MessengerOldestCheck implements HealthCheckInterface
{
    public function __construct(
        private MessengerQueuesInterface $queues,
        private ClockInterface $clock,
        private int $warningMinutes = 15,
        private int $criticalMinutes = 60,
    ) {
    }

    public function identifier(): string
    {
        return 'data.messenger_oldest';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Data;
    }

    public function run(): HealthResult
    {
        // Red stub (T-295-01): reads its collaborators and answers nothing yet; the green commit replaces it.
        $this->queues->oldestMessage((string) ($this->warningMinutes + $this->criticalMinutes));
        $this->clock->now();

        return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.result.check_failed'));
    }
}
