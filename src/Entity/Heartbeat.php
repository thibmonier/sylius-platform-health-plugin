<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Entity;

/**
 * The last beat of one declared console command (US-296): one row per command, never a log of its runs. Its table is
 * written and read through DBAL by DbalHeartbeatStore; the mapping exists so that the schema, the migration and
 * doctrine:schema:validate agree.
 */
class Heartbeat
{
    public function __construct(
        private readonly string $command,
        private readonly \DateTimeImmutable $firstRecordedAt,
        private readonly \DateTimeImmutable $lastRunAt,
        private readonly int $lastExitCode,
        private readonly ?\DateTimeImmutable $lastSuccessAt,
    ) {
    }

    public function command(): string
    {
        return $this->command;
    }

    /**
     * When the command was recorded for the first time: the reference of a command declared but never run.
     */
    public function firstRecordedAt(): \DateTimeImmutable
    {
        return $this->firstRecordedAt;
    }

    public function lastRunAt(): \DateTimeImmutable
    {
        return $this->lastRunAt;
    }

    public function lastExitCode(): int
    {
        return $this->lastExitCode;
    }

    public function lastSuccessAt(): ?\DateTimeImmutable
    {
        return $this->lastSuccessAt;
    }
}
