<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Doctrine\Migrations\DependencyFactory;

/**
 * Reads the status that `doctrine:migrations:status` shows, without executing or recording anything.
 */
final readonly class DoctrineMigrationStatusReader implements MigrationStatusReaderInterface
{
    public function __construct(
        private ?DependencyFactory $dependencyFactory,
    ) {
    }

    public function read(): ?MigrationStatus
    {
        if (!$this->dependencyFactory instanceof \Doctrine\Migrations\DependencyFactory) {
            return null;
        }

        $calculator = $this->dependencyFactory->getMigrationStatusCalculator();

        return new MigrationStatus(\count($calculator->getNewMigrations()), \count($calculator->getExecutedUnavailableMigrations()));
    }
}
