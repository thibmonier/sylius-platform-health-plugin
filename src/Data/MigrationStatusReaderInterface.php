<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

interface MigrationStatusReaderInterface
{
    /**
     * Null when the application has no Doctrine migrations configured.
     */
    public function read(): ?MigrationStatus;
}
