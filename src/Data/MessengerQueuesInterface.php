<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

/**
 * The Messenger transports of the application, read without consuming, retrying or removing a message.
 */
interface MessengerQueuesInterface
{
    /**
     * Transports a worker consumes: neither a failure transport, nor sync://, nor in-memory://.
     *
     * @return list<string>
     */
    public function consumableTransports(): array;

    public function failureTransportOf(string $transport): ?string;

    /**
     * Null when the transport cannot count its messages.
     */
    public function countMessages(string $transport): ?int;

    public function oldestMessage(string $transport): OldestMessage;
}
