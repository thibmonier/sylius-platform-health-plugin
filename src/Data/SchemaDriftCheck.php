<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
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
        $statements = $this->reader->countStatements();
        if (null === $statements) {
            return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.data.schema_drift.not_available'));
        }
        if ($statements > 0) {
            return HealthResult::warning(new TranslatableMessage('tmonier_platform_health.data.schema_drift.drift', ['%count%' => $statements]), null, DocumentationLink::create());
        }

        return HealthResult::ok(new TranslatableMessage('tmonier_platform_health.data.schema_drift.ok'));
    }
}
