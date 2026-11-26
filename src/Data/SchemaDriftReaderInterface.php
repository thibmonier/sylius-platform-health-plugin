<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

interface SchemaDriftReaderInterface
{
    /**
     * Number of SQL statements that would bring the database in line with the mapping; null without a Doctrine ORM.
     */
    public function countStatements(): ?int;
}
