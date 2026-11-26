<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support;

use Tmonier\SyliusPlatformHealthPlugin\Data\MessengerQueuesInterface;
use Tmonier\SyliusPlatformHealthPlugin\Data\OldestMessage;

final class FakeMessengerQueues implements MessengerQueuesInterface
{
    /** @var array<string, ?int> */
    private array $counts = [];

    /** @var array<string, OldestMessage> */
    private array $oldest = [];

    /** @var array<string, string> */
    private array $failures = [];

    /** @var list<string> */
    private array $consumable = [];

    public function withTransport(string $name, ?int $count = 0, ?OldestMessage $oldest = null, ?string $failureTransport = null): self
    {
        $this->consumable[] = $name;
        $this->counts[$name] = $count;
        $this->oldest[$name] = $oldest ?? OldestMessage::emptyQueue();
        if (null !== $failureTransport) {
            $this->failures[$name] = $failureTransport;
        }

        return $this;
    }

    public function withFailureTransport(string $name, ?int $count): self
    {
        $this->counts[$name] = $count;

        return $this;
    }

    public function consumableTransports(): array
    {
        return $this->consumable;
    }

    public function failureTransportOf(string $transport): ?string
    {
        return $this->failures[$transport] ?? null;
    }

    public function countMessages(string $transport): ?int
    {
        return $this->counts[$transport] ?? null;
    }

    public function oldestMessage(string $transport): OldestMessage
    {
        return $this->oldest[$transport] ?? OldestMessage::notMeasurable();
    }
}
