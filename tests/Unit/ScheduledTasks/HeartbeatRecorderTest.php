<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\ScheduledTasks;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\FakeHeartbeatStore;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\RecordingLogger;
use Tmonier\SyliusPlatformHealthPlugin\ScheduledTasks\HeartbeatRecorder;

final class HeartbeatRecorderTest extends TestCase
{
    private const NOW = '2026-11-26 12:00:00 UTC';

    public function testItListensToTheEndOfEveryCommandFailuresIncluded(): void
    {
        self::assertSame([ConsoleEvents::TERMINATE => 'onTerminate'], HeartbeatRecorder::getSubscribedEvents());
    }

    public function testTheEndOfADeclaredCommandIsRecordedWithItsExitCode(): void
    {
        $store = new FakeHeartbeatStore();

        $this->terminate($store, 'tmonier:feeds:generate', 0);
        $this->terminate($store, 'tmonier:feeds:generate', 1);

        self::assertEquals(
            [['tmonier:feeds:generate', 0, new \DateTimeImmutable(self::NOW)], ['tmonier:feeds:generate', 1, new \DateTimeImmutable(self::NOW)]],
            $store->recorded,
        );
    }

    public function testAnUndeclaredCommandIsNeverRecorded(): void
    {
        $store = new FakeHeartbeatStore();

        $this->terminate($store, 'cache:clear', 0);

        self::assertSame([], $store->recorded);
    }

    public function testAFailureToRecordIsLoggedAndTheCommandKeepsItsExitCode(): void
    {
        $logger = new RecordingLogger();
        $failure = new \RuntimeException('database unreachable');

        $event = $this->terminate(new FakeHeartbeatStore($failure), 'tmonier:feeds:generate', 0, $logger);

        self::assertSame(0, $event->getExitCode());
        self::assertSame([$failure], $logger->loggedExceptions());
    }

    private function terminate(FakeHeartbeatStore $store, string $command, int $exitCode, ?RecordingLogger $logger = null): ConsoleTerminateEvent
    {
        $event = new ConsoleTerminateEvent(new Command($command), new ArrayInput([]), new NullOutput(), $exitCode);
        (new HeartbeatRecorder($store, new MockClock(self::NOW), $logger ?? new RecordingLogger(), ['tmonier:feeds:generate', 'app:demo:reset']))->onTerminate($event);

        return $event;
    }
}
