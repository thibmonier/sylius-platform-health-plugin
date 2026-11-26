<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\ScheduledTasks;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Translation\TranslatableMessage;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\FakeHeartbeatStore;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\RecordingLogger;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;
use Tmonier\SyliusPlatformHealthPlugin\Health\MessageList;
use Tmonier\SyliusPlatformHealthPlugin\ScheduledTasks\HeartbeatCheck;

final class HeartbeatCheckTest extends TestCase
{
    use ResultAssertions;

    private const NOW = '2026-11-26 12:00:00 UTC';

    private const FEEDS = 'tmonier:feeds:generate';

    private const RESET = 'app:demo:reset';

    public function testWithoutDeclaredCommandTheProbeSaysSoAndIsOk(): void
    {
        self::assertSummary($this->execute(new FakeHeartbeatStore(), []), HealthStatus::Ok, 'tmonier_platform_health.scheduled_tasks.heartbeat.none');
    }

    public function testACommandThatSucceededWithinItsRhythmIsOkWithItsLastSuccess(): void
    {
        $store = (new FakeHeartbeatStore())->with(self::FEEDS, '2026-11-20 08:00:00 UTC', '2026-11-26 11:50:00 UTC', 0, '2026-11-26 11:50:00 UTC');

        $result = $this->execute($store);

        self::assertSummary($result, HealthStatus::Ok, 'tmonier_platform_health.scheduled_tasks.heartbeat.ok', ['%count%' => 1]);
        self::assertSame([['tmonier_platform_health.scheduled_tasks.heartbeat.line_ok', ['%command%' => self::FEEDS, '%last_success%' => '2026-11-26 11:50 UTC']]], $this->lines($result));
    }

    public function testAStoppedCronIsCriticalWithItsNameAndTheTimeOfItsLastSuccess(): void
    {
        $store = (new FakeHeartbeatStore())->with(self::FEEDS, '2026-11-20 08:00:00 UTC', '2026-11-26 10:50:00 UTC', 0, '2026-11-26 10:50:00 UTC');

        $result = $this->execute($store);

        self::assertSummary($result, HealthStatus::Critical, 'tmonier_platform_health.scheduled_tasks.heartbeat.late', ['%command%' => self::FEEDS, '%last_success%' => '2026-11-26 10:50 UTC']);
        self::assertEquals(DocumentationLink::create(), $result->action);
    }

    public function testTwoMissedRhythmsWarn(): void
    {
        $store = (new FakeHeartbeatStore())->with(self::FEEDS, '2026-11-20 08:00:00 UTC', '2026-11-26 11:25:00 UTC', 0, '2026-11-26 11:25:00 UTC');

        self::assertSummary($this->execute($store), HealthStatus::Warning, 'tmonier_platform_health.scheduled_tasks.heartbeat.late', ['%command%' => self::FEEDS]);
    }

    public function testACronThatRunsOnTimeButFailsWarnsWithItsExitCode(): void
    {
        $store = (new FakeHeartbeatStore())->with(self::FEEDS, '2026-11-20 08:00:00 UTC', '2026-11-26 11:59:00 UTC', 1, '2026-11-26 11:20:00 UTC');

        $result = $this->execute($store);

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.scheduled_tasks.heartbeat.failed', ['%command%' => self::FEEDS, '%exit_code%' => 1]);
        self::assertEquals(DocumentationLink::create(), $result->action);
    }

    public function testASingleFailureAfterARecentSuccessAlreadyWarns(): void
    {
        $store = (new FakeHeartbeatStore())->with(self::FEEDS, '2026-11-20 08:00:00 UTC', '2026-11-26 11:59:00 UTC', 2, '2026-11-26 11:45:00 UTC');

        self::assertSummary($this->execute($store), HealthStatus::Warning, 'tmonier_platform_health.scheduled_tasks.heartbeat.failed', ['%exit_code%' => 2]);
    }

    public function testACronThatKeepsFailingBeyondFourRhythmsIsCritical(): void
    {
        $store = (new FakeHeartbeatStore())->with(self::FEEDS, '2026-11-20 08:00:00 UTC', '2026-11-26 11:59:00 UTC', 1, '2026-11-26 10:30:00 UTC');

        self::assertSame(HealthStatus::Critical, $this->execute($store)->status);
    }

    public function testACommandThatNeverSucceededIsMeasuredFromItsFirstRecord(): void
    {
        $store = (new FakeHeartbeatStore())->with(self::FEEDS, '2026-11-26 10:30:00 UTC', '2026-11-26 11:59:00 UTC', 1, null);

        self::assertSummary($this->execute($store), HealthStatus::Critical, 'tmonier_platform_health.scheduled_tasks.heartbeat.never_succeeded', ['%command%' => self::FEEDS]);
    }

    public function testACommandNeverRunIsUnknownAndNoExceptionEscapes(): void
    {
        self::assertSummary($this->execute(new FakeHeartbeatStore()), HealthStatus::Unknown, 'tmonier_platform_health.scheduled_tasks.heartbeat.not_yet', ['%command%' => self::FEEDS]);
    }

    public function testACommandNeverRunWhileTheRecordingStartedRecentlyIsStillUnknown(): void
    {
        $store = (new FakeHeartbeatStore())->with(self::RESET, '2026-11-26 11:50:00 UTC', '2026-11-26 11:50:00 UTC', 0, '2026-11-26 11:50:00 UTC');

        $result = $this->execute($store, [self::FEEDS => 900, self::RESET => 86400]);

        self::assertSummary($result, HealthStatus::Unknown, 'tmonier_platform_health.scheduled_tasks.heartbeat.not_yet', ['%command%' => self::FEEDS]);
    }

    public function testACommandNeverRunTwoRhythmsAfterTheRecordingStartedWarns(): void
    {
        $store = (new FakeHeartbeatStore())->with(self::RESET, '2026-11-26 09:00:00 UTC', '2026-11-26 11:50:00 UTC', 0, '2026-11-26 11:50:00 UTC');

        $result = $this->execute($store, [self::FEEDS => 900, self::RESET => 86400]);

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.scheduled_tasks.heartbeat.never_ran', ['%command%' => self::FEEDS]);
    }

    public function testTheWorstCommandIsTheSummaryAndEachDeclaredCommandHasItsLine(): void
    {
        $store = (new FakeHeartbeatStore())
            ->with(self::RESET, '2026-11-20 08:00:00 UTC', '2026-11-26 03:00:00 UTC', 0, '2026-11-26 03:00:00 UTC')
            ->with(self::FEEDS, '2026-11-20 08:00:00 UTC', '2026-11-26 10:50:00 UTC', 0, '2026-11-26 10:50:00 UTC')
        ;

        $result = $this->execute($store, [self::RESET => 86400, self::FEEDS => 900]);

        self::assertSummary($result, HealthStatus::Critical, 'tmonier_platform_health.scheduled_tasks.heartbeat.late', ['%command%' => self::FEEDS]);
        self::assertSame(
            ['tmonier_platform_health.scheduled_tasks.heartbeat.line_ok', 'tmonier_platform_health.scheduled_tasks.heartbeat.late'],
            array_column($this->lines($result), 0),
        );
    }

    public function testTheThresholdsAreConfigurable(): void
    {
        $store = (new FakeHeartbeatStore())->with(self::FEEDS, '2026-11-20 08:00:00 UTC', '2026-11-26 11:20:00 UTC', 0, '2026-11-26 11:20:00 UTC');

        self::assertSame(HealthStatus::Ok, (new HeartbeatCheck($store, new MockClock(self::NOW), new RecordingLogger(), [self::FEEDS => 900], 3, 6))->run()->status);
    }

    public function testAnUnreadableTableIsUnknownTheCauseLoggedNeverDisplayed(): void
    {
        $logger = new RecordingLogger();
        $failure = new \RuntimeException('SQLSTATE[42S02]: table tmonier_platform_health_heartbeat on db.internal not found');

        $result = (new HeartbeatCheck(new FakeHeartbeatStore($failure), new MockClock(self::NOW), $logger, [self::FEEDS => 900]))->run();

        self::assertSummary($result, HealthStatus::Unknown, 'tmonier_platform_health.scheduled_tasks.heartbeat.unavailable');
        self::assertEquals(DocumentationLink::create(), $result->action);
        self::assertSame([$failure], $logger->loggedExceptions());
        self::assertStringNotContainsString('db.internal', self::serialized($result));
    }

    /**
     * @param array<string, int> $heartbeats
     */
    private function execute(FakeHeartbeatStore $store, array $heartbeats = [self::FEEDS => 900]): HealthResult
    {
        return (new HeartbeatCheck($store, new MockClock(self::NOW), new RecordingLogger(), $heartbeats, 2, 4))->run();
    }

    /**
     * @return list<array{string, array<mixed>}>
     */
    private function lines(HealthResult $result): array
    {
        self::assertInstanceOf(MessageList::class, $result->detail);

        $lines = [];
        foreach ($result->detail->messages as $line) {
            self::assertInstanceOf(TranslatableMessage::class, $line);
            $lines[] = [$line->getMessage(), $line->getParameters()];
        }

        return $lines;
    }
}
