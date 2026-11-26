<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final readonly class MessengerPendingCheck implements HealthCheckInterface
{
    public function __construct(
        private MessengerQueuesInterface $queues,
        private int $warningCount = 100,
    ) {
    }

    public function identifier(): string
    {
        return 'data.messenger_pending';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Data;
    }

    public function run(): HealthResult
    {
        $fullest = null;
        $notCountable = null;
        foreach ($this->queues->consumableTransports() as $transport) {
            $count = $this->queues->countMessages($transport);
            if (null === $count) {
                $notCountable ??= $transport;

                continue;
            }
            if ($count > $this->warningCount && (null === $fullest || $count > $fullest[1])) {
                $fullest = [$transport, $count];
            }
        }

        return match (true) {
            [] === $this->queues->consumableTransports() => HealthResult::ok(new TranslatableMessage('tmonier_platform_health.data.messenger.no_transport')),
            null !== $fullest => HealthResult::warning(new TranslatableMessage('tmonier_platform_health.data.messenger_pending.over', ['%transport%' => $fullest[0], '%count%' => $fullest[1], '%threshold%' => $this->warningCount]), null, DocumentationLink::create()),
            null !== $notCountable => HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.data.messenger_pending.not_countable', ['%transport%' => $notCountable])),
            default => HealthResult::ok(new TranslatableMessage('tmonier_platform_health.data.messenger_pending.ok', ['%threshold%' => $this->warningCount])),
        };
    }
}
