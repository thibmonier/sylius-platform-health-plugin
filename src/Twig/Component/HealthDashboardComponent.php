<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Twig\Component;

use Psr\Log\LoggerInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthSnapshotProvider;
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthDashboardPanel;
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthSummaryBuilder;

/**
 * Skeleton of the dashboard panel (US-289): the tests of T-289-01 are written against this contract, the behaviour comes with T-289-02 and T-289-03.
 */
final readonly class HealthDashboardComponent
{
    public function __construct(
        private HealthSnapshotProvider $provider,
        private HealthSummaryBuilder $summaryBuilder,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @phpstan-impure
     */
    public function getPanel(): ?HealthDashboardPanel
    {
        $snapshot = $this->provider->snapshot();
        $this->logger->debug('The dashboard panel is not implemented yet.', ['reports' => \count($snapshot->reports)]);

        return [] === $snapshot->reports ? null : new HealthDashboardPanel($this->summaryBuilder->build($snapshot->reports));
    }
}
