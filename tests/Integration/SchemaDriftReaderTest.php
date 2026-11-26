<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tmonier\SyliusPlatformHealthPlugin\Data\SchemaDriftReaderInterface;

/**
 * US-318: the drift is what `doctrine:schema:update --dump-sql` prints. That command hides the table where Doctrine
 * Migrations records the executed versions (DoctrineMigrationsBundle filters it during doctrine:schema:* only); on the
 * page, outside any command, the probe counted it as a table to drop, so the probe could never be `ok`.
 */
final class SchemaDriftReaderTest extends KernelTestCase
{
    public function testTheTableOfTheExecutedMigrationsIsNotCountedAsDrift(): void
    {
        self::bootKernel();
        $table = $this->metadataTable();
        // Whatever built the database (migrations in the CI, schema locally), the table exists, as in any project.
        $this->dependencyFactory()->getMetadataStorage()->ensureInitialized();
        $statements = $this->statementsOutsideAnyCommand();
        $dropOfTheTable = array_values(array_filter($statements, static fn (string $sql): bool => 1 === preg_match('/^DROP TABLE ' . preg_quote($table, '/') . '\b/i', $sql)));
        self::assertCount(1, $dropOfTheTable, sprintf('The database of the test application, built by the migrations, has the table "%s".', $table));

        self::assertSame(\count($statements) - 1, $this->reader()->countStatements());
    }

    public function testTheSchemaFilterOfTheConnectionIsLeftAsItWas(): void
    {
        self::bootKernel();
        $configuration = $this->entityManager()->getConnection()->getConfiguration();
        $filter = $configuration->getSchemaAssetsFilter();

        $this->reader()->countStatements();

        self::assertSame($filter, $configuration->getSchemaAssetsFilter());
    }

    private function reader(): SchemaDriftReaderInterface
    {
        $reader = self::getContainer()->get('tmonier_sylius_platform_health.test.data.schema_drift_reader');
        self::assertInstanceOf(SchemaDriftReaderInterface::class, $reader);

        return $reader;
    }

    /** @return list<string> */
    private function statementsOutsideAnyCommand(): array
    {
        $entityManager = $this->entityManager();

        return (new SchemaTool($entityManager))->getUpdateSchemaSql($entityManager->getMetadataFactory()->getAllMetadata());
    }

    private function metadataTable(): string
    {
        $storage = $this->dependencyFactory()->getConfiguration()->getMetadataStorageConfiguration();
        self::assertInstanceOf(TableMetadataStorageConfiguration::class, $storage);

        return $storage->getTableName();
    }

    private function dependencyFactory(): DependencyFactory
    {
        $factory = self::getContainer()->get('tmonier_sylius_platform_health.test.migrations_dependency_factory');
        self::assertInstanceOf(DependencyFactory::class, $factory);

        return $factory;
    }

    private function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
