<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Health;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\FakeCheck;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\RecordingLogger;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckReport;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class HealthCheckRunnerTest extends TestCase
{
    private const SECRET = 'SQLSTATE[HY000] mysql://root:hunter2@db.internal:3306/shop';

    private RecordingLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new RecordingLogger();
    }

    public function testThreeChecksGiveThreeReportsWithTheirIdentifierCategoryAndStatus(): void
    {
        $reports = $this->runner(
            $this->check('environment.php', HealthCategory::Environment, HealthStatus::Ok),
            $this->check('data.migrations', HealthCategory::Data, HealthStatus::Warning),
            $this->check('shop.channels', HealthCategory::Shop, HealthStatus::Critical),
        )->run();

        self::assertSame(
            [
                ['environment.php', HealthCategory::Environment, HealthStatus::Ok],
                ['data.migrations', HealthCategory::Data, HealthStatus::Warning],
                ['shop.channels', HealthCategory::Shop, HealthStatus::Critical],
            ],
            array_map(static fn (HealthCheckReport $report): array => [$report->identifier, $report->category, $report->result->status], $reports),
        );
    }

    public function testWithoutAnyCheckTheRunnerGivesAnEmptyCollection(): void
    {
        self::assertSame([], $this->runner()->run());
    }

    public function testACheckSlowerThanItsBudgetBecomesUnknownWithTheBudgetReason(): void
    {
        $slow = new FakeCheck('data.slow', HealthCategory::Data, static function (): HealthResult {
            usleep(150_000);

            return HealthResult::ok(new TranslatableMessage('tests.slow.ok'));
        });

        $reports = $this->runnerWithBudget(50, $slow)->run();

        self::assertSame(HealthStatus::Unknown, $reports[0]->result->status);
        self::assertInstanceOf(TranslatableMessage::class, $reports[0]->result->summary);
        self::assertSame('tmonier_platform_health.result.budget_exceeded', $reports[0]->result->summary->getMessage());
        self::assertSame(['%budget_ms%' => 50], $reports[0]->result->summary->getParameters());
    }

    public function testTheOtherChecksAnswerNormallyWhenOneIsTooSlow(): void
    {
        $slow = new FakeCheck('data.slow', HealthCategory::Data, static function (): HealthResult {
            usleep(150_000);

            return HealthResult::ok(new TranslatableMessage('tests.slow.ok'));
        });

        $reports = $this->runnerWithBudget(
            50,
            $this->check('environment.php', HealthCategory::Environment, HealthStatus::Ok),
            $slow,
            $this->check('shop.channels', HealthCategory::Shop, HealthStatus::Critical),
        )->run();

        self::assertSame(
            [HealthStatus::Ok, HealthStatus::Unknown, HealthStatus::Critical],
            array_map(static fn (HealthCheckReport $report): HealthStatus => $report->result->status, $reports),
        );
    }

    public function testACheckWithinItsBudgetKeepsItsOwnResult(): void
    {
        $reports = $this->runner($this->check('data.fast', HealthCategory::Data, HealthStatus::Warning))->run();

        self::assertSame(HealthStatus::Warning, $reports[0]->result->status);
    }

    public function testAThrowingCheckBecomesUnknown(): void
    {
        $reports = $this->runner($this->throwingCheck(), $this->check('shop.channels', HealthCategory::Shop, HealthStatus::Ok))->run();

        self::assertSame(['data.broken', 'shop.channels'], array_map(static fn (HealthCheckReport $report): string => $report->identifier, $reports));
        self::assertSame([HealthStatus::Unknown, HealthStatus::Ok], array_map(static fn (HealthCheckReport $report): HealthStatus => $report->result->status, $reports));
    }

    public function testNothingIsRaisedToTheCallerWhenACheckThrowsAnError(): void
    {
        $error = new FakeCheck('data.error', HealthCategory::Data, static fn (): HealthResult => throw new \TypeError(self::SECRET));

        $reports = $this->runner($error)->run();

        self::assertCount(1, $reports);
    }

    public function testTheExceptionIsLoggedWithItsTrace(): void
    {
        $this->runner($this->throwingCheck())->run();

        $logged = $this->logger->loggedExceptions();
        self::assertCount(1, $logged);
        self::assertSame(self::SECRET, $logged[0]->getMessage());
        self::assertNotSame('', $logged[0]->getTraceAsString());
    }

    public function testTheSummaryContainsNeitherTheTraceNorTheRawMessage(): void
    {
        $reports = $this->runner($this->throwingCheck())->run();

        $rendered = print_r($reports[0]->result, true);
        self::assertStringNotContainsString('hunter2', $rendered);
        self::assertStringNotContainsString('SQLSTATE', $rendered);
        self::assertStringNotContainsString('.php', $rendered);
        self::assertSame('tmonier_platform_health.result.check_failed', $reports[0]->result->summary instanceof TranslatableMessage ? $reports[0]->result->summary->getMessage() : null);
    }

    private function runner(HealthCheckInterface ...$checks): HealthCheckRunner
    {
        return $this->runnerWithBudget(500, ...$checks);
    }

    private function runnerWithBudget(int $budgetMs, HealthCheckInterface ...$checks): HealthCheckRunner
    {
        return new HealthCheckRunner($checks, $this->logger, $budgetMs);
    }

    private function check(string $identifier, HealthCategory $category, HealthStatus $status): HealthCheckInterface
    {
        return new FakeCheck($identifier, $category, static fn (): HealthResult => new HealthResult($status, new TranslatableMessage('tests.' . $identifier)));
    }

    private function throwingCheck(): HealthCheckInterface
    {
        return new FakeCheck('data.broken', HealthCategory::Data, static fn (): HealthResult => throw new \RuntimeException(self::SECRET));
    }
}
