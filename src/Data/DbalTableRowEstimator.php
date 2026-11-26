<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;

/**
 * Reads the row estimate the database keeps in its catalogue (MySQL and MariaDB: information_schema.TABLES, PostgreSQL:
 * pg_class), never a COUNT(*) that would scan the very table that grew. The estimate lags behind: it is an order of
 * magnitude, which is what a purge that stopped needs.
 */
final readonly class DbalTableRowEstimator implements TableRowEstimatorInterface
{
    private const MYSQL = 'SELECT TABLE_NAME AS name, TABLE_ROWS AS estimate FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN (?)';

    // reltuples is -1 for a table never analysed (PostgreSQL 14+): read as 0 until the autovacuum measures it.
    private const POSTGRESQL = 'SELECT c.relname AS name, GREATEST(c.reltuples, 0)::bigint AS estimate FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace WHERE n.nspname = current_schema() AND c.relkind IN (\'r\', \'p\') AND c.relname IN (?)';

    public function __construct(
        private Connection $connection,
    ) {
    }

    public function estimate(array $tables): ?array
    {
        $sql = $this->sqlFor($this->connection->getDatabasePlatform());
        if (null === $sql) {
            return null;
        }
        if ([] === $tables) {
            return [];
        }

        $estimates = [];
        foreach ($this->connection->fetchAllAssociative($sql, [$tables], [ArrayParameterType::STRING]) as $row) {
            if (\is_string($row['name']) && is_numeric($row['estimate'])) {
                $estimates[$row['name']] = (int) $row['estimate'];
            }
        }

        return $estimates;
    }

    private function sqlFor(object $platform): ?string
    {
        return match (true) {
            $platform instanceof AbstractMySQLPlatform => self::MYSQL,
            $platform instanceof PostgreSQLPlatform => self::POSTGRESQL,
            default => null,
        };
    }
}
