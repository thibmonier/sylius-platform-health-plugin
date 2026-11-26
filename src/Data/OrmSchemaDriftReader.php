<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Counts what `doctrine:schema:update --dump-sql` would print; the statements themselves never leave this class.
 *
 * That command hides the table where Doctrine Migrations records the executed versions (DoctrineMigrationsBundle
 * filters it during doctrine:schema:* only); outside any command it would read as a table to drop, so the reader
 * hides it the same way for the time of the comparison, then puts the filter of the connection back.
 */
final readonly class OrmSchemaDriftReader implements SchemaDriftReaderInterface
{
    public function __construct(
        private ?EntityManagerInterface $entityManager,
        private ?DependencyFactory $dependencyFactory = null,
    ) {
    }

    public function countStatements(): ?int
    {
        if (!$this->entityManager instanceof EntityManagerInterface) {
            return null;
        }

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $configuration = $this->entityManager->getConnection()->getConfiguration();
        $filter = $configuration->getSchemaAssetsFilter();
        $hidden = $this->migrationsTable();
        if (null !== $hidden) {
            $configuration->setSchemaAssetsFilter(static fn (string|AbstractAsset $asset): bool => ($asset instanceof AbstractAsset ? $asset->getName() : $asset) !== $hidden && $filter($asset));
        }

        try {
            return \count((new SchemaTool($this->entityManager))->getUpdateSchemaSql($metadata));
        } finally {
            $configuration->setSchemaAssetsFilter($filter);
        }
    }

    private function migrationsTable(): ?string
    {
        $storage = $this->dependencyFactory?->getConfiguration()->getMetadataStorageConfiguration();

        return $storage instanceof TableMetadataStorageConfiguration ? $storage->getTableName() : null;
    }
}
