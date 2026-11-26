<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface;
use Symfony\Component\Messenger\Bridge\Doctrine\Transport\Connection as DoctrineTransportConnection;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;

/**
 * Filled by MessengerTransportsPass: the receivers, and for each transport its DSN (resolved by the container at runtime,
 * never displayed) and whether it is a failure transport. The age of the oldest message is read on the table of a
 * Doctrine transport with MIN(available_at), which its index serves; any other transport is not measurable.
 */
final readonly class SymfonyMessengerQueues implements MessengerQueuesInterface
{
    private const IGNORED_SCHEMES = ['sync://', 'in-memory://'];

    /**
     * @param array<string, array{dsn: string, options: array<string, mixed>, failure: bool}> $transports
     * @param array<string, string>                                                            $failureTransports failure transport of each transport
     */
    public function __construct(
        private ContainerInterface $receivers,
        private array $transports,
        private array $failureTransports,
        private ?ManagerRegistry $doctrine,
    ) {
    }

    public function consumableTransports(): array
    {
        $names = [];
        foreach ($this->transports as $name => $transport) {
            if (!$transport['failure'] && !$this->ignored($transport['dsn'])) {
                $names[] = $name;
            }
        }

        return $names;
    }

    public function failureTransportOf(string $transport): ?string
    {
        return $this->failureTransports[$transport] ?? null;
    }

    public function countMessages(string $transport): ?int
    {
        try {
            $receiver = $this->receivers->get($transport);
        } catch (\Throwable) {
            return null;
        }

        return $receiver instanceof MessageCountAwareInterface ? $receiver->getMessageCount() : null;
    }

    public function oldestMessage(string $transport): OldestMessage
    {
        $connection = $this->doctrineQueue($transport);
        if (null === $connection) {
            return OldestMessage::notMeasurable();
        }

        [$dbal, $table, $queue] = $connection;
        $availableAt = $dbal->fetchOne(sprintf('SELECT MIN(available_at) FROM %s WHERE queue_name = ? AND delivered_at IS NULL', $table), [$queue]);

        return \is_string($availableAt)
            ? OldestMessage::waitingSince(new \DateTimeImmutable($availableAt, new \DateTimeZone('UTC')))
            : OldestMessage::emptyQueue();
    }

    /**
     * @return array{Connection, string, string}|null
     */
    private function doctrineQueue(string $transport): ?array
    {
        $definition = $this->transports[$transport] ?? null;
        if (null === $definition || !$this->doctrine instanceof \Doctrine\Persistence\ManagerRegistry || !str_starts_with($definition['dsn'], 'doctrine://') || !class_exists(DoctrineTransportConnection::class)) {
            return null;
        }

        $configuration = DoctrineTransportConnection::buildConfiguration($definition['dsn'], $definition['options']);
        $table = $configuration['table_name'] ?? null;
        $queue = $configuration['queue_name'] ?? null;
        if (!\is_string($table) || 1 !== preg_match('/^[A-Za-z0-9_.]+$/', $table) || !\is_string($queue)) {
            return null;
        }

        $dbal = $this->doctrine->getConnection(\is_string($configuration['connection'] ?? null) ? $configuration['connection'] : null);

        return $dbal instanceof Connection ? [$dbal, $table, $queue] : null;
    }

    private function ignored(string $dsn): bool
    {
        foreach (self::IGNORED_SCHEMES as $scheme) {
            if (str_starts_with($dsn, $scheme)) {
                return true;
            }
        }

        return false;
    }
}
