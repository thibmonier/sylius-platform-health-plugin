<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\Fixture\BeatCommand;
use Tmonier\SyliusPlatformHealthPlugin\Entity\Heartbeat;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;
use Tmonier\SyliusPlatformHealthPlugin\Migrations\Version20261126120000;
use Tmonier\SyliusPlatformHealthPlugin\ScheduledTasks\DbalHeartbeatStore;
use Tmonier\SyliusPlatformHealthPlugin\ScheduledTasks\HeartbeatStoreInterface;

/**
 * The heartbeat on the test application: a declared console command really run, its row in the database, the probe.
 */
final class HeartbeatTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
        $this->connection()->executeStatement('DELETE FROM ' . DbalHeartbeatStore::TABLE);
    }

    protected function tearDown(): void
    {
        // The pages of the other tests read the heartbeat of the test application without any run: never ours.
        self::bootKernel();
        $this->connection()->executeStatement('DELETE FROM ' . DbalHeartbeatStore::TABLE);
        parent::tearDown();
    }

    public function testASuccessfulRunOfADeclaredCommandIsItsLastSuccess(): void
    {
        self::assertSame(0, $this->runConsole(BeatCommand::NAME));

        $heartbeat = $this->store()->all()[BeatCommand::NAME] ?? null;
        self::assertInstanceOf(Heartbeat::class, $heartbeat);
        self::assertSame(0, $heartbeat->lastExitCode());
        self::assertNotNull($heartbeat->lastSuccessAt());
        self::assertEquals($heartbeat->lastRunAt(), $heartbeat->lastSuccessAt());
    }

    public function testAFailedRunKeepsTheLastSuccessAndOneRowPerCommand(): void
    {
        $this->runConsole(BeatCommand::NAME);
        $success = ($this->store()->all()[BeatCommand::NAME] ?? null)?->lastSuccessAt();
        self::assertNotNull($success);

        self::assertSame(1, $this->runConsole(BeatCommand::NAME, ['--fail' => true]));

        $heartbeat = $this->store()->all()[BeatCommand::NAME] ?? null;
        self::assertInstanceOf(Heartbeat::class, $heartbeat);
        self::assertSame(1, $heartbeat->lastExitCode());
        self::assertEquals($success, $heartbeat->lastSuccessAt());
        self::assertEquals(1, $this->connection()->fetchOne('SELECT COUNT(*) FROM ' . DbalHeartbeatStore::TABLE));
    }

    public function testAnUndeclaredCommandIsNeverRecorded(): void
    {
        self::assertSame(0, $this->runConsole('list'));

        self::assertSame([], $this->store()->all());
    }

    public function testTheProbeReadsTheDeclaredCommandOnTimeAfterItsRun(): void
    {
        $this->runConsole(BeatCommand::NAME);

        $runner = self::getContainer()->get('tmonier_sylius_platform_health.test.runner');
        self::assertInstanceOf(HealthCheckRunner::class, $runner);
        $reports = array_filter($runner->run(), static fn (\Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport $report): bool => 'scheduled_tasks.heartbeat' === $report->identifier);
        $report = array_values($reports)[0] ?? null;

        self::assertNotNull($report);
        self::assertSame(HealthCategory::ScheduledTasks, $report->category);
        self::assertSame(HealthStatus::Ok, $report->result->status);
    }

    public function testTheMigrationCreatesTheTableTheMappingDescribes(): void
    {
        $entityManager = self::getContainer()->get('doctrine.orm.default_entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $platform = $this->connection()->getDatabasePlatform();

        $migrated = new \Doctrine\DBAL\Schema\Schema();
        (new Version20261126120000($this->connection(), new NullLogger()))->up($migrated);
        $mapped = (new SchemaTool($entityManager))->getSchemaFromMetadata([$entityManager->getClassMetadata(Heartbeat::class)]);

        // Same columns and primary key; the column order and the table options of the connection do not matter.
        self::assertSame(
            $this->definitions($platform->getCreateTableSQL($mapped->getTable(DbalHeartbeatStore::TABLE))[0]),
            $this->definitions($platform->getCreateTableSQL($migrated->getTable(DbalHeartbeatStore::TABLE))[0]),
        );
    }

    /**
     * @return list<string>
     */
    private function definitions(string $createTable): array
    {
        if (1 !== preg_match('/^CREATE TABLE \S+ \((.*)\)/s', $createTable, $match)) {
            self::fail('Not a CREATE TABLE statement: ' . $createTable);
        }
        $definitions = preg_split('/,\s(?![^(]*\))/', $match[1]) ?: [];
        sort($definitions);

        return $definitions;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function runConsole(string $name, array $options = []): int
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);
        $application = new Application($kernel);
        $application->setAutoExit(false);

        return $application->run(new ArrayInput(['command' => $name] + $options), new NullOutput());
    }

    private function store(): HeartbeatStoreInterface
    {
        $store = self::getContainer()->get('tmonier_sylius_platform_health.test.heartbeat_store');
        self::assertInstanceOf(HeartbeatStoreInterface::class, $store);

        return $store;
    }

    private function connection(): Connection
    {
        $connection = self::getContainer()->get('doctrine.dbal.default_connection');
        self::assertInstanceOf(Connection::class, $connection);

        return $connection;
    }
}
