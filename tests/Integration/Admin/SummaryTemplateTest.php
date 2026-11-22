<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\Admin;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthActionLink;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthSummaryBuilder;
use Twig\Environment;

/**
 * The summary template on its own, fed with hand-made reports: the states that the test application's probes
 * cannot produce (no probe at all, a link to a route that does not exist).
 */
final class SummaryTemplateTest extends KernelTestCase
{
    public function testWithoutAnyCheckThePageSaysSoAndPointsToTheDocumentation(): void
    {
        $crawler = $this->render([]);

        self::assertCount(1, $crawler->filter('[data-test-health-empty]'));
        self::assertStringContainsString('No health check is installed', $crawler->filter('[data-test-health-empty]')->text());
        self::assertStringContainsString('github.com/thibmonier/sylius-platform-health-plugin', (string) $crawler->filter('[data-test-health-empty] a')->attr('href'));
        self::assertCount(0, $crawler->filter('[data-test-health-category]'));
    }

    public function testAnUnknownCheckIsShownAsNotMeasurableWithItsShortReason(): void
    {
        $crawler = $this->render([$this->report('plugins.a', HealthResult::unknown(new TranslatableMessage('Redis is not installed')))]);

        $row = $crawler->filter('[data-test-health-check="plugins.a"]');
        self::assertCount(1, $row);
        self::assertStringContainsString('Not measurable', $row->text());
        self::assertStringContainsString('Redis is not installed', $row->text());
    }

    public function testACategoryOfUnknownChecksIsNotShownAsCritical(): void
    {
        $crawler = $this->render([$this->report('plugins.a', HealthResult::unknown(new TranslatableMessage('Redis is not installed')))]);

        self::assertCount(1, $crawler->filter('[data-test-health-category="plugins"]'));
        self::assertSame('unknown', $crawler->filter('[data-test-health-category="plugins"]')->attr('data-test-health-status'));
    }

    public function testTheDetailOfEachCheckIsFoldedByDefault(): void
    {
        $crawler = $this->render([$this->report('plugins.a', HealthResult::ok(new TranslatableMessage('Fine')))]);

        self::assertCount(1, $crawler->filter('details[data-test-health-details]'));
        self::assertCount(0, $crawler->filter('details[open]'));
    }

    public function testACheckWithAnActionLinkGetsTheUrlOfItsRoute(): void
    {
        $crawler = $this->render([$this->report('shop.a', HealthResult::critical(new TranslatableMessage('Seven products have no price'), action: new HealthActionLink('sylius_admin_product_index', ['page' => 2])))]);

        self::assertCount(1, $crawler->filter('[data-test-health-check="shop.a"] [data-test-health-action]'));
        self::assertSame('/admin/products/?page=2', $crawler->filter('[data-test-health-check="shop.a"] [data-test-health-action]')->attr('href'));
    }

    public function testACheckWithoutActionLinkShowsItsSummaryWithoutLink(): void
    {
        $crawler = $this->render([$this->report('shop.a', HealthResult::warning(new TranslatableMessage('Seven products have no price')))]);

        self::assertCount(1, $crawler->filter('[data-test-health-check="shop.a"]'));
        self::assertStringContainsString('Seven products have no price', $crawler->filter('[data-test-health-check="shop.a"]')->text());
        self::assertCount(0, $crawler->filter('[data-test-health-action]'));
    }

    public function testALinkToARouteThatDoesNotExistGivesNoLinkAndDoesNotBreakThePage(): void
    {
        $crawler = $this->render([
            $this->report('shop.a', HealthResult::warning(new TranslatableMessage('Broken link'), action: new HealthActionLink('no_such_route'))),
            $this->report('shop.b', HealthResult::ok(new TranslatableMessage('Still readable'))),
        ]);

        self::assertCount(1, $crawler->filter('[data-test-health-check="shop.b"]'));
        self::assertCount(0, $crawler->filter('[data-test-health-action]'));
        self::assertStringContainsString('Still readable', $crawler->filter('[data-test-health-check="shop.b"]')->text());
    }

    /**
     * @param list<HealthCheckReport> $reports
     */
    private function render(array $reports): Crawler
    {
        self::bootKernel();
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        $html = $twig->render('@TmonierSyliusPlatformHealthPlugin/admin/_summary.html.twig', ['categories' => (new HealthSummaryBuilder())->build($reports)]);

        return new Crawler($html);
    }

    private function report(string $identifier, HealthResult $result): HealthCheckReport
    {
        return new HealthCheckReport($identifier, HealthCategory::from(explode('.', $identifier)[0]), $result, 1.0);
    }
}
