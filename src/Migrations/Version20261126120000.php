<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20261126120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the heartbeat table of the scheduled tasks (tmonier/sylius-platform-health-plugin, US-296).';
    }

    public function up(Schema $schema): void
    {
        $heartbeat = $schema->createTable('tmonier_platform_health_heartbeat');
        $heartbeat->addColumn('command', Types::STRING, ['length' => 255]);
        $heartbeat->addColumn('first_recorded_at', Types::DATETIME_IMMUTABLE);
        $heartbeat->addColumn('last_run_at', Types::DATETIME_IMMUTABLE);
        $heartbeat->addColumn('last_exit_code', Types::INTEGER);
        $heartbeat->addColumn('last_success_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $heartbeat->setPrimaryKey(['command']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('tmonier_platform_health_heartbeat');
    }
}
