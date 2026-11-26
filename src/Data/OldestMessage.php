<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

/**
 * What a transport says of its oldest waiting message: not measurable (not a Doctrine transport), an empty queue, or the
 * moment the oldest message became available.
 */
final readonly class OldestMessage
{
    private function __construct(
        public bool $measurable,
        public ?\DateTimeImmutable $waitingSince,
    ) {
    }

    public static function notMeasurable(): self
    {
        return new self(false, null);
    }

    public static function emptyQueue(): self
    {
        return new self(true, null);
    }

    public static function waitingSince(\DateTimeImmutable $availableAt): self
    {
        return new self(true, $availableAt);
    }
}
