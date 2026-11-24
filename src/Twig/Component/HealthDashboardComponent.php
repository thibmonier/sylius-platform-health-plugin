<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Twig\Component;

use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthSnapshotProvider;
use Tmonier\SyliusPlatformHealthPlugin\Security\PlatformHealthVoter;
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthDashboardPanel;
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthSummaryBuilder;

/**
 * The panel of the administration dashboard. Like the page, the dashboard renders it with loading="defer": the skeleton is
 * answered at once and the results are read afterwards, from the cache shared with the page (never a probe while the
 * dashboard itself is rendered). It holds no public property, so nothing is hydrated from the browser.
 *
 * A panel that fails shows nothing: the dashboard is the first page after the login, it must not depend on a probe.
 */
// The component is served by the route of the administration (/admin/{_locale}/_components/...), which only asks for
// ROLE_ADMINISTRATION_ACCESS: the guard belongs to the class, so that the URL of the component is refused as the page is.
#[AsLiveComponent]
#[IsGranted(PlatformHealthVoter::VIEW)]
final class HealthDashboardComponent
{
    use DefaultActionTrait;

    private bool $loaded = false;

    private ?HealthDashboardPanel $panel = null;

    public function __construct(
        private readonly HealthSnapshotProvider $provider,
        private readonly HealthSummaryBuilder $summaryBuilder,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getPanel(): ?HealthDashboardPanel
    {
        if (!$this->loaded) {
            $this->panel = $this->load();
            $this->loaded = true;
        }

        return $this->panel;
    }

    private function load(): ?HealthDashboardPanel
    {
        try {
            return new HealthDashboardPanel($this->summaryBuilder->build($this->provider->snapshot()->reports));
        } catch (\Throwable $exception) {
            $this->logger->error('The platform health panel of the dashboard could not be built.', ['exception' => $exception]);

            return null;
        }
    }
}
