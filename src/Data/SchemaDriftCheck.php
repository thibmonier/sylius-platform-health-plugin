<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final readonly class SchemaDriftCheck implements HealthCheckInterface
{
    public function __construct(
        private SchemaDriftReaderInterface $reader,
    ) {
    }

    public function identifier(): string
    {
        return 'data.schema_drift';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Data;
    }

    public function run(): HealthResult
    {
        // Red stub (T-294-01): reads its collaborators and answers nothing yet; the green commit replaces it.
        $this->reader->countStatements();

        return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.result.check_failed'));
    }
}
