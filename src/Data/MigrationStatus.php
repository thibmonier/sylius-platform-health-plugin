<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

final readonly class MigrationStatus
{
    /**
     * @param int $pending migrations of the code not executed yet
     * @param int $unknownExecuted migrations executed on the database that the code no longer knows
     */
    public function __construct(
        public int $pending,
        public int $unknownExecuted,
    ) {
    }
}
