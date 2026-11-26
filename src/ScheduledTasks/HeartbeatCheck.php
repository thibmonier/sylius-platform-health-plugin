<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\ScheduledTasks;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Entity\Heartbeat;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;
use Tmonier\SyliusPlatformHealthPlugin\Health\MessageList;

final readonly class HeartbeatCheck implements HealthCheckInterface
{
    private const SEVERITY = ['ok' => 0, 'unknown' => 1, 'warning' => 2, 'critical' => 3];

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
        if ([] === $this->heartbeats) {
            return HealthResult::ok(new TranslatableMessage('tmonier_platform_health.scheduled_tasks.heartbeat.none'));
        }

        try {
            $recorded = $this->store->all();
        } catch (\Throwable $exception) {
            // The cause may name the database server: the log keeps it, the page never shows it.
            $this->logger->error('The heartbeats of the scheduled tasks could not be read.', ['exception' => $exception]);

            return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.scheduled_tasks.heartbeat.unavailable'), null, DocumentationLink::create());
        }

        $recordingSince = $this->recordingSince($recorded);
        $worst = null;
        $lines = [];
        foreach ($this->heartbeats as $command => $seconds) {
            [$status, $line] = $this->assess((string) $command, $seconds, $recorded[$command] ?? null, $recordingSince);
            $lines[] = $line;
            if (null === $worst || self::SEVERITY[$status->value] > self::SEVERITY[$worst[0]->value]) {
                $worst = [$status, $line];
            }
        }

        return $this->result($worst, $lines);
    }

    /**
     * @return array{HealthStatus, TranslatableMessage}
     */
    private function assess(string $command, int $seconds, ?Heartbeat $heartbeat, ?\DateTimeImmutable $recordingSince): array
    {
        $now = $this->clock->now();
        if (!$heartbeat instanceof \Tmonier\SyliusPlatformHealthPlugin\Entity\Heartbeat) {
            return $recordingSince instanceof \DateTimeImmutable && $now->getTimestamp() - $recordingSince->getTimestamp() > $this->warningBeats * $seconds
                ? [HealthStatus::Warning, $this->message('tmonier_platform_health.scheduled_tasks.heartbeat.never_ran', $command)]
                : [HealthStatus::Unknown, $this->message('tmonier_platform_health.scheduled_tasks.heartbeat.not_yet', $command)];
        }

        $lastSuccess = $heartbeat->lastSuccessAt();
        $beats = ($now->getTimestamp() - ($lastSuccess ?? $heartbeat->firstRecordedAt())->getTimestamp()) / $seconds;
        $late = $lastSuccess instanceof \DateTimeImmutable
            ? $this->message('tmonier_platform_health.scheduled_tasks.heartbeat.late', $command, ['%last_success%' => $this->time($lastSuccess)])
            : $this->message('tmonier_platform_health.scheduled_tasks.heartbeat.never_succeeded', $command);

        return match (true) {
            $beats > $this->criticalBeats => [HealthStatus::Critical, $late],
            0 !== $heartbeat->lastExitCode() => [HealthStatus::Warning, $this->message('tmonier_platform_health.scheduled_tasks.heartbeat.failed', $command, ['%exit_code%' => $heartbeat->lastExitCode()])],
            $beats > $this->warningBeats => [HealthStatus::Warning, $late],
            default => [HealthStatus::Ok, $this->message('tmonier_platform_health.scheduled_tasks.heartbeat.line_ok', $command, ['%last_success%' => $this->time($lastSuccess ?? $heartbeat->lastRunAt())])],
        };
    }

    /**
     * @param array{HealthStatus, TranslatableMessage} $worst
     * @param list<TranslatableMessage>               $lines
     */
    private function result(array $worst, array $lines): HealthResult
    {
        [$status, $summary] = $worst;
        $detail = new MessageList($lines);

        return match ($status) {
            HealthStatus::Ok => HealthResult::ok(new TranslatableMessage('tmonier_platform_health.scheduled_tasks.heartbeat.ok', ['%count%' => \count($lines)]), $detail),
            HealthStatus::Unknown => HealthResult::unknown($summary, $detail),
            HealthStatus::Warning => HealthResult::warning($summary, $detail, DocumentationLink::create()),
            HealthStatus::Critical => HealthResult::critical($summary, $detail, DocumentationLink::create()),
        };
    }

    /**
     * When the recording started: the first record of any command, the reference of a command never run.
     *
     * @param array<string, Heartbeat> $recorded
     */
    private function recordingSince(array $recorded): ?\DateTimeImmutable
    {
        $first = null;
        foreach ($recorded as $heartbeat) {
            if (null === $first || $heartbeat->firstRecordedAt() < $first) {
                $first = $heartbeat->firstRecordedAt();
            }
        }

        return $first;
    }

    /**
     * @param array<string, scalar> $parameters
     */
    private function message(string $key, string $command, array $parameters = []): TranslatableMessage
    {
        return new TranslatableMessage($key, ['%command%' => $command] + $parameters);
    }

    private function time(\DateTimeImmutable $at): string
    {
        return $at->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i') . ' UTC';
    }
}
