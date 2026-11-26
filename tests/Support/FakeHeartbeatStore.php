<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support;

use Tmonier\SyliusPlatformHealthPlugin\Entity\Heartbeat;
use Tmonier\SyliusPlatformHealthPlugin\ScheduledTasks\HeartbeatStoreInterface;

final class FakeHeartbeatStore implements HeartbeatStoreInterface
{
    /** @var list<array{string, int, \DateTimeImmutable}> */
    public array $recorded = [];

    /** @var array<string, Heartbeat> */
    private array $heartbeats = [];

    public function __construct(
        private readonly ?\Throwable $failure = null,
    ) {
    }

    public function with(string $command, string $firstRecordedAt, string $lastRunAt, int $lastExitCode, ?string $lastSuccessAt): self
    {
        $this->heartbeats[$command] = new Heartbeat(
            $command,
            new \DateTimeImmutable($firstRecordedAt),
            new \DateTimeImmutable($lastRunAt),
            $lastExitCode,
            null === $lastSuccessAt ? null : new \DateTimeImmutable($lastSuccessAt),
        );

        return $this;
    }

    public function record(string $command, int $exitCode, \DateTimeImmutable $at): void
    {
        if ($this->failure instanceof \Throwable) {
            throw $this->failure;
        }
        $this->recorded[] = [$command, $exitCode, $at];
    }

    public function all(): array
    {
        if ($this->failure instanceof \Throwable) {
            throw $this->failure;
        }

        return $this->heartbeats;
    }
}
