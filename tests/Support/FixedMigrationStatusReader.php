<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support;

use Tmonier\SyliusPlatformHealthPlugin\Data\MigrationStatus;
use Tmonier\SyliusPlatformHealthPlugin\Data\MigrationStatusReaderInterface;

/**
 * Every migration executed: the state the page tests of the test application expect.
 */
final class FixedMigrationStatusReader implements MigrationStatusReaderInterface
{
    public function read(): MigrationStatus
    {
        return new MigrationStatus(0, 0);
    }
}
