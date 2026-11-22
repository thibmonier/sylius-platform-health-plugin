<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture;

/**
 * Counts the runs of the probes of the test application: the tests and the kernel share one PHP process.
 */
final class ProbeExecutions
{
    private static int $count = 0;

    public static function record(): void
    {
        ++self::$count;
    }

    /**
     * @phpstan-impure
     */
    public static function count(): int
    {
        return self::$count;
    }

    public static function reset(): void
    {
        self::$count = 0;
    }
}
