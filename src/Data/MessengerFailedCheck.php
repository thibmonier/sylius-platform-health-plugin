<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
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
        $transports = $this->queues->consumableTransports();
        if ([] === $transports) {
            return HealthResult::ok(new TranslatableMessage('tmonier_platform_health.data.messenger.no_transport'));
        }

        $unprotected = [];
        $failureTransports = [];
        foreach ($transports as $transport) {
            $failureTransport = $this->queues->failureTransportOf($transport);
            if (null === $failureTransport) {
                $unprotected[] = $transport;
            } else {
                $failureTransports[$failureTransport] = true;
            }
        }

        $failed = 0;
        foreach (array_keys($failureTransports) as $failureTransport) {
            $count = $this->queues->countMessages($failureTransport);
            if (null === $count) {
                return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.data.messenger_failed.not_countable', ['%transport%' => $failureTransport]));
            }
            $failed += $count;
        }

        return $this->result($failed, $unprotected);
    }

    /**
     * @param list<string> $unprotected transports whose failed messages are lost
     */
    private function result(int $failed, array $unprotected): HealthResult
    {
        $missing = [] === $unprotected ? null : new TranslatableMessage('tmonier_platform_health.data.messenger_failed.no_failure_transport', ['%transports%' => implode(', ', $unprotected)]);

        if ($failed > 0) {
            $summary = new TranslatableMessage('tmonier_platform_health.data.messenger_failed.failed', ['%count%' => $failed]);

            return $failed > $this->criticalCount
                ? HealthResult::critical($summary, $missing, DocumentationLink::create())
                : HealthResult::warning($summary, $missing, DocumentationLink::create());
        }

        return $missing instanceof \Symfony\Component\Translation\TranslatableMessage
            ? HealthResult::warning($missing, null, DocumentationLink::create())
            : HealthResult::ok(new TranslatableMessage('tmonier_platform_health.data.messenger_failed.ok'));
    }
}
