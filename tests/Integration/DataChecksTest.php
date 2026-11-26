<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Logging\Middleware;
use Doctrine\Migrations\DependencyFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\RecordingLogger;
use Tmonier\SyliusPlatformHealthPlugin\Data\ConnectionPingerInterface;
use Tmonier\SyliusPlatformHealthPlugin\Data\DbalTableRowEstimator;
use Tmonier\SyliusPlatformHealthPlugin\Data\DoctrineMigrationStatusReader;
use Tmonier\SyliusPlatformHealthPlugin\Data\SchemaDriftReaderInterface;
use Tmonier\SyliusPlatformHealthPlugin\Data\TableRowEstimatorInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

/**
 * The four data probes against the database of the test application (MySQL, MariaDB and PostgreSQL in the CI).
 */
final class DataChecksTest extends KernelTestCase
{
    public function testThePluginShipsTheFourDataProbesWithoutAnyConfiguration(): void
    {
        $categories = [];
        foreach ($this->runner()->run() as $report) {
            $categories[$report->identifier] = $report->category;
        }

        foreach (['data.connection', 'data.pending_migrations', 'data.schema_drift', 'data.table_growth'] as $identifier) {
            self::assertSame(HealthCategory::Data, $categories[$identifier] ?? null, $identifier);
        }
    }

    public function testTheConnectionProbeIsOkOnTheDatabaseOfTheTestApplication(): void
    {
        self::assertSame(HealthStatus::Ok, $this->statusOf('data.connection'));
    }

    public function testTheGrowthProbeReadsTheRealCatalogueOfTheDeclaredTable(): void
    {
        self::assertSame(HealthStatus::Ok, $this->statusOf('data.table_growth'));
    }

    public function testThePingerReallyQueriesTheDatabase(): void
    {
        [$connection, $logger] = $this->recordingConnection();
        (new \Tmonier\SyliusPlatformHealthPlugin\Data\DbalConnectionPinger($connection))->ping();

        self::assertNotSame([], $this->executedSql($logger));
    }

    public function testTheEstimatorReadsTheCatalogueAndNeverCountsTheRows(): void
    {
        [$connection, $logger] = $this->recordingConnection();
        $estimates = (new DbalTableRowEstimator($connection))->estimate(['sylius_channel', 'no_such_table_at_all']);

        self::assertIsArray($estimates);
        self::assertArrayHasKey('sylius_channel', $estimates);
        self::assertGreaterThanOrEqual(0, $estimates['sylius_channel']);
        self::assertArrayNotHasKey('no_such_table_at_all', $estimates);
        self::assertNotSame([], $this->executedSql($logger));
        foreach ($this->executedSql($logger) as $sql) {
            self::assertDoesNotMatchRegularExpression('/COUNT\s*\(/i', $sql);
        }
    }

    public function testTheReadersOfTheTestApplicationAnswer(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $pinger = $container->get('tmonier_sylius_platform_health.test.data.connection_pinger');
        self::assertInstanceOf(ConnectionPingerInterface::class, $pinger);
        $factory = $container->get('tmonier_sylius_platform_health.test.migrations_dependency_factory');
        self::assertInstanceOf(DependencyFactory::class, $factory);
        self::assertNotNull((new DoctrineMigrationStatusReader($factory))->read(), 'The test application has Doctrine migrations.');
        $drift = $container->get('tmonier_sylius_platform_health.test.data.schema_drift_reader');
        self::assertInstanceOf(SchemaDriftReaderInterface::class, $drift);
        self::assertNotNull($drift->countStatements(), 'The test application has a Doctrine ORM.');
        $estimator = $container->get('tmonier_sylius_platform_health.test.data.table_row_estimator');
        self::assertInstanceOf(TableRowEstimatorInterface::class, $estimator);
        self::assertNotNull($estimator->estimate(['sylius_channel']), 'MySQL, MariaDB and PostgreSQL are read.');
    }

    private function runner(): HealthCheckRunner
    {
        self::bootKernel();
        $runner = self::getContainer()->get('tmonier_sylius_platform_health.test.runner');
        self::assertInstanceOf(HealthCheckRunner::class, $runner);

        return $runner;
    }

    private function statusOf(string $identifier): ?HealthStatus
    {
        foreach ($this->runner()->run() as $report) {
            if ($identifier === $report->identifier) {
                return $report->result->status;
            }
        }

        return null;
    }

    /**
     * A connection to the same database whose every query is recorded.
     *
     * @return array{Connection, RecordingLogger}
     */
    private function recordingConnection(): array
    {
        self::bootKernel();
        $default = self::getContainer()->get('doctrine.dbal.default_connection');
        self::assertInstanceOf(Connection::class, $default);
        $logger = new RecordingLogger();

        /** @phpstan-ignore argument.type (the parameters of the default connection, passed as they are) */
        return [DriverManager::getConnection($default->getParams(), (new Configuration())->setMiddlewares([new Middleware($logger)])), $logger];
    }

    /**
     * @return list<string>
     */
    private function executedSql(RecordingLogger $logger): array
    {
        $sql = [];
        foreach ($logger->records as $record) {
            if (\is_string($record['context']['sql'] ?? null)) {
                $sql[] = $record['context']['sql'];
            }
        }

        return $sql;
    }
}
