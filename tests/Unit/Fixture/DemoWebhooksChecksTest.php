<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Fixture;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\Health\BrokenCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Fixture\DemoWebhooks\Health\OutboxCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\RecordingLogger;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

/**
 * A probe provided by another plugin that throws is a probe like any other: "unknown", the cause in the log, the others answer.
 */
final class DemoWebhooksChecksTest extends TestCase
{
    private RecordingLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new RecordingLogger();
    }

    public function testAProvidedProbeThatThrowsIsUnknownAndTheOthersStillAnswer(): void
    {
        $reports = (new HealthCheckRunner([new BrokenCheck(), new OutboxCheck()], $this->logger))->run();

        self::assertSame(
            ['demo_webhooks.broken' => HealthStatus::Unknown, 'demo_webhooks.outbox' => HealthStatus::Ok],
            array_column(array_map(static fn (HealthCheckReport $report): array => [$report->identifier, $report->result->status], $reports), 1, 0),
        );
    }

    public function testTheCauseIsLoggedWithItsTraceAndNotShownOnTheResult(): void
    {
        $reports = (new HealthCheckRunner([new BrokenCheck()], $this->logger))->run();

        $logged = $this->logger->loggedExceptions();
        self::assertCount(1, $logged);
        self::assertSame(BrokenCheck::MESSAGE, $logged[0]->getMessage());
        self::assertInstanceOf(TranslatableMessage::class, $reports[0]->result->summary);
        self::assertSame('tmonier_platform_health.result.check_failed', $reports[0]->result->summary->getMessage());
    }
}
