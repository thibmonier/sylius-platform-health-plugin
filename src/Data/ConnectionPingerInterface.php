<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

interface ConnectionPingerInterface
{
    /**
     * Sends a trivial query to the default connection; throws when the database does not answer.
     */
    public function ping(): void;
}
