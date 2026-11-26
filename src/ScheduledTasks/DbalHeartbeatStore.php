<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\ScheduledTasks;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Tmonier\SyliusPlatformHealthPlugin\Entity\Heartbeat;

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
        $now = $this->utc($at);
        if (0 < $this->update($command, $exitCode, $now)) {
            return;
        }

        try {
            $this->connection->insert(self::TABLE, [
                'command' => $command,
                'first_recorded_at' => $now,
                'last_run_at' => $now,
                'last_exit_code' => $exitCode,
                'last_success_at' => 0 === $exitCode ? $now : null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Another run of the same command inserted the row first, or MySQL reported no change for an update within
            // the same second: the row is there, update it.
            $this->update($command, $exitCode, $now);
        }
    }

    public function all(): array
    {
        $heartbeats = [];
        foreach ($this->connection->fetchAllAssociative('SELECT command, first_recorded_at, last_run_at, last_exit_code, last_success_at FROM ' . self::TABLE) as $row) {
            $command = (string) $this->text($row['command']);
            $heartbeats[$command] = new Heartbeat(
                $command,
                $this->date($row['first_recorded_at']) ?? new \DateTimeImmutable('@0'),
                $this->date($row['last_run_at']) ?? new \DateTimeImmutable('@0'),
                (int) $this->text($row['last_exit_code']),
                $this->date($row['last_success_at']),
            );
        }

        return $heartbeats;
    }

    private function update(string $command, int $exitCode, string $now): int
    {
        $success = 0 === $exitCode ? ', last_success_at = :now' : '';

        return (int) $this->connection->executeStatement(
            'UPDATE ' . self::TABLE . ' SET last_run_at = :now, last_exit_code = :code' . $success . ' WHERE command = :command',
            ['now' => $now, 'code' => $exitCode, 'command' => $command],
        );
    }

    private function utc(\DateTimeImmutable $at): string
    {
        return $at->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    private function text(mixed $value): ?string
    {
        return \is_string($value) || \is_int($value) ? (string) $value : null;
    }

    private function date(mixed $value): ?\DateTimeImmutable
    {
        $text = $this->text($value);

        return null === $text ? null : new \DateTimeImmutable($text, new \DateTimeZone('UTC'));
    }
}
