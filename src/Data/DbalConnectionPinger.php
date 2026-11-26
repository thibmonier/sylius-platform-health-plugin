<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Doctrine\DBAL\Connection;

final readonly class DbalConnectionPinger implements ConnectionPingerInterface
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function ping(): void
    {
        $this->connection->executeQuery($this->connection->getDatabasePlatform()->getDummySelectSQL())->free();
    }
}
