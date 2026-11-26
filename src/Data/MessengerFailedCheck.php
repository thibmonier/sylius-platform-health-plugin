<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final readonly class MessengerFailedCheck implements HealthCheckInterface
{
    public function __construct(
        private MessengerQueuesInterface $queues,
        private int $criticalCount = 100,
    ) {
    }

    public function identifier(): string
    {
        return 'data.messenger_failed';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Data;
    }

    public function run(): HealthResult
    {
        // Red stub (T-295-01): reads its collaborators and answers nothing yet; the green commit replaces it.
        $this->queues->countMessages((string) $this->criticalCount);

        return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.result.check_failed'));
    }
}
