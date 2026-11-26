<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

interface TableRowEstimatorInterface
{
    /**
     * Estimated rows of the given tables, read from the catalogue of the database, never counted.
     *
     * @param list<string> $tables
     *
     * @return array<string, int>|null null for a platform the estimator cannot read; a table absent from the result was not found
     */
    public function estimate(array $tables): ?array;
}
