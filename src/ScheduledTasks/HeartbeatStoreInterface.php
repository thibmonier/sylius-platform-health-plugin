<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\ScheduledTasks;

use Tmonier\SyliusPlatformHealthPlugin\Entity\Heartbeat;

interface HeartbeatStoreInterface
{
    /**
     * Updates the row of the command, or inserts it on its first run. A zero exit code also moves its last success.
     */
    public function record(string $command, int $exitCode, \DateTimeImmutable $at): void;

    /**
     * @return array<string, Heartbeat> keyed by command
     */
    public function all(): array;
}
