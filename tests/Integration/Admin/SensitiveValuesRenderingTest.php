<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration\Admin;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Translation\TranslatableMessage;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Summary\HealthSummaryBuilder;
use Twig\Environment;

/**
 * A probe that lets a secret or a trace slip: the page the administrator reads must not show it.
 */
final class SensitiveValuesRenderingTest extends KernelTestCase
{
    public function testThePasswordOfADsnInTheSummaryOrTheDetailIsNotInTheResponse(): void
    {
        $html = $this->html(HealthResult::critical(
            new TranslatableMessage('Cannot connect to mysql://shop:s3cret@db.internal/sylius'),
            new TranslatableMessage('password=hunter2 refused'),
        ));

        self::assertStringNotContainsString('s3cret', $html);
        self::assertStringNotContainsString('hunter2', $html);
        self::assertStringContainsString('mysql://***@db.internal/sylius', $html);
    }

    public function testAnExecutionTraceInTheDetailIsReplacedByAGenericMessage(): void
    {
        $crawler = new Crawler($html = $this->html(HealthResult::warning(
            new TranslatableMessage('Something is wrong'),
            new TranslatableMessage("Stack trace:\n#0 /var/www/src/Foo.php(12): Foo->bar()\n#1 {main}"),
        )));

        self::assertStringNotContainsString('Foo.php', $html);
        self::assertStringNotContainsString('Stack trace', $html);
        self::assertStringContainsString('The detail was hidden', $crawler->filter('[data-test-health-detail]')->text());
    }

    public function testTheAbsolutePathOfTheServerIsReducedToTheProjectPath(): void
    {
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        self::assertIsString($projectDir);

        $html = $this->html(HealthResult::warning(new TranslatableMessage('Unreadable'), new TranslatableMessage(sprintf('Cannot read %s/config/secrets.yaml', $projectDir))));

        self::assertStringNotContainsString($projectDir, $html);
        self::assertStringContainsString('config/secrets.yaml', $html);
    }

    private function html(HealthResult $result): string
    {
        self::bootKernel();
        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);
        $report = new HealthCheckReport('data.leaky', HealthCategory::Data, $result, 1.0);

        return $twig->render('@TmonierSyliusPlatformHealthPlugin/admin/_summary.html.twig', ['categories' => (new HealthSummaryBuilder())->build([$report])]);
    }
}
