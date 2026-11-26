<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Counts what `doctrine:schema:update --dump-sql` would print; the statements themselves never leave this class.
 */
final readonly class OrmSchemaDriftReader implements SchemaDriftReaderInterface
{
    public function __construct(
        private ?EntityManagerInterface $entityManager,
    ) {
    }

    public function countStatements(): ?int
    {
        if (!$this->entityManager instanceof \Doctrine\ORM\EntityManagerInterface) {
            return null;
        }

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();

        return \count((new SchemaTool($this->entityManager))->getUpdateSchemaSql($metadata));
    }
}
