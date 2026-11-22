<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Twig\Component;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthSnapshot;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthSnapshotProvider;
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthCategorySummary;
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthSummaryBuilder;

/**
 * The health summary of the administration page. The page renders it with loading="defer": the skeleton is
 * answered at once and the probes only run when the browser asks for the results, never in the constructor
 * nor while the skeleton is rendered. It holds no public property, so nothing is hydrated from the browser.
 */
#[AsLiveComponent]
final class HealthComponent
{
    use DefaultActionTrait;

    private bool $forceRefresh = false;

    private ?HealthSnapshot $snapshot = null;

    public function __construct(
        private readonly HealthSnapshotProvider $provider,
        private readonly HealthSummaryBuilder $summaryBuilder,
    ) {
    }

    #[LiveAction]
    public function refresh(): void
    {
        $this->forceRefresh = true;
        $this->snapshot = null;
    }

    public function getSnapshot(): HealthSnapshot
    {
        return $this->snapshot ??= $this->provider->snapshot($this->forceRefresh);
    }

    /**
     * @return list<HealthCategorySummary>
     */
    public function getCategories(): array
    {
        return $this->summaryBuilder->build($this->getSnapshot()->reports);
    }
}
