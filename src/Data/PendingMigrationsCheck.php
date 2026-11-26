<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Data;

use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;

final readonly class PendingMigrationsCheck implements HealthCheckInterface
{
    public function __construct(
        private MigrationStatusReaderInterface $reader,
    ) {
    }

    public function identifier(): string
    {
        return 'data.pending_migrations';
    }

    public function category(): HealthCategory
    {
        return HealthCategory::Data;
    }

    public function run(): HealthResult
    {
        $status = $this->reader->read();
        if (!$status instanceof MigrationStatus) {
            return HealthResult::unknown(new TranslatableMessage('tmonier_platform_health.data.pending_migrations.not_configured'));
        }
        if ($status->pending > 0) {
            return HealthResult::critical(new TranslatableMessage('tmonier_platform_health.data.pending_migrations.pending', ['%count%' => $status->pending]), null, DocumentationLink::create());
        }
        if ($status->unknownExecuted > 0) {
            return HealthResult::warning(new TranslatableMessage('tmonier_platform_health.data.pending_migrations.unknown_executed', ['%count%' => $status->unknownExecuted]), null, DocumentationLink::create());
        }

        return HealthResult::ok(new TranslatableMessage('tmonier_platform_health.data.pending_migrations.ok'));
    }
}
