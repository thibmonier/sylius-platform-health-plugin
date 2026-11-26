<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Psr\Clock\ClockInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
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
        $transports = $this->queues->consumableTransports();
        if ([] === $transports) {
            return HealthResult::ok(new TranslatableMessage('tmonier_platform_health.data.messenger.no_transport'));
        }

        $measured = false;
        $oldest = null;
        foreach ($transports as $transport) {
            $message = $this->queues->oldestMessage($transport);
            $measured = $measured || $message->measurable;
            if ($message->waitingSince instanceof \DateTimeImmutable && (null === $oldest || $message->waitingSince < $oldest[1])) {
                $oldest = [$transport, $message->waitingSince];
            }
        }

        if (!$measured) {
            return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.data.messenger_oldest.not_measurable'));
        }

        return null === $oldest ? $this->ok() : $this->aged($oldest[0], $this->minutesSince($oldest[1]));
    }

    private function aged(string $transport, int $minutes): HealthResult
    {
        if ($minutes <= $this->warningMinutes) {
            return $this->ok();
        }

        $summary = new TranslatableMessage('tmonier_platform_health.data.messenger_oldest.late', ['%transport%' => $transport, '%minutes%' => $minutes]);

        return $minutes > $this->criticalMinutes
            ? HealthResult::critical($summary, null, DocumentationLink::create())
            : HealthResult::warning($summary, null, DocumentationLink::create());
    }

    private function ok(): HealthResult
    {
        return HealthResult::ok(new TranslatableMessage('tmonier_platform_health.data.messenger_oldest.ok', ['%minutes%' => $this->warningMinutes]));
    }

    private function minutesSince(\DateTimeImmutable $availableAt): int
    {
        return max(0, intdiv($this->clock->now()->getTimestamp() - $availableAt->getTimestamp(), 60));
    }
}
