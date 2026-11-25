<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture;

use Symfony\Component\Clock\ClockInterface;

/**
 * The clock of the snapshot provider in the test application: it tells the time, unless a test makes it fail, which is
 * the way to get a result provider that throws (the provider is final). The tests and the kernel share one PHP process.
 */
final class FailingClock implements ClockInterface
{
    public const MESSAGE = 'The clock is out of order (secret-clock-detail).';

    private static bool $failing = false;

    public static function failFromNowOn(): void
    {
        self::$failing = true;
    }

    public static function recover(): void
    {
        self::$failing = false;
    }

    public function now(): \DateTimeImmutable
    {
        if (self::$failing) {
            throw new \RuntimeException(self::MESSAGE);
        }

        return new \DateTimeImmutable();
    }

    public function sleep(float|int $seconds): void
    {
    }

    public function withTimeZone(\DateTimeZone|string $timezone): static
    {
        return $this;
    }
}
