<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;

/**
 * Counts what `doctrine:schema:update --dump-sql` would print; the statements themselves never leave this class.
 *
 * That command never drops the table where Doctrine Migrations records the executed versions: DoctrineMigrationsBundle
 * hides it during doctrine:schema:* only. Outside any command the comparison reads it as a table to drop, so that one
 * statement is not counted. The schema filter of the connection is left untouched (its type differs between DBAL 3 and 4).
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
        $statements = (new SchemaTool($this->entityManager))->getUpdateSchemaSql($metadata);
        $table = $this->migrationsTable();
        if (null === $table) {
            return \count($statements);
        }

        $dropOfTheTable = '/^DROP TABLE [`"]?' . preg_quote($table, '/') . '[`"]?$/i';

        return \count(array_filter($statements, static fn (string $sql): bool => 1 !== preg_match($dropOfTheTable, $sql)));
    }

    private function migrationsTable(): ?string
    {
        $storage = $this->dependencyFactory?->getConfiguration()->getMetadataStorageConfiguration();

        return $storage instanceof TableMetadataStorageConfiguration ? $storage->getTableName() : null;
    }
}
