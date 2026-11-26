<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\ScheduledTasks;

use Doctrine\DBAL\Connection;

/**
 * Through DBAL, never the entity manager: a flush at the end of a command would also write what the command itself left
 * unflushed. Dates are stored in UTC.
 */
final readonly class DbalHeartbeatStore implements HeartbeatStoreInterface
{
    public const TABLE = 'tmonier_platform_health_heartbeat';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function record(string $command, int $exitCode, \DateTimeImmutable $at): void
    {
        // Red stub (T-296-01): writes nothing yet; the green commit (T-296-02) replaces it.
        unset($command, $exitCode, $at);
        $this->connection->isTransactionActive();
    }

    public function all(): array
    {
        $this->connection->isTransactionActive();

        return [];
    }
}
