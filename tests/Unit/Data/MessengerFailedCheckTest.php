<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Data;

use PHPUnit\Framework\TestCase;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\FakeMessengerQueues;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Data\MessengerFailedCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class MessengerFailedCheckTest extends TestCase
{
    use ResultAssertions;

    public function testEmptyFailureTransportsAreOk(): void
    {
        $queues = (new FakeMessengerQueues())->withTransport('main', 0, null, 'main_failed')->withFailureTransport('main_failed', 0);

        self::assertSummary($this->execute($queues), HealthStatus::Ok, 'tmonier_platform_health.data.messenger_failed.ok');
    }

    public function testFailedMessagesWarnWithTheirCountAndNothingIsRetried(): void
    {
        $queues = (new FakeMessengerQueues())->withTransport('main', 0, null, 'main_failed')->withFailureTransport('main_failed', 3);

        $result = $this->execute($queues);

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.data.messenger_failed.failed', ['%count%' => 3]);
        self::assertEquals(DocumentationLink::create(), $result->action);
    }

    public function testAFailureTransportSharedByTwoTransportsIsCountedOnce(): void
    {
        $queues = (new FakeMessengerQueues())
            ->withTransport('main', 0, null, 'failed')
            ->withTransport('async', 0, null, 'failed')
            ->withFailureTransport('failed', 3)
        ;

        self::assertSummary($this->execute($queues), HealthStatus::Warning, 'tmonier_platform_health.data.messenger_failed.failed', ['%count%' => 3]);
    }

    public function testBeyondTheCriticalCountTheProbeIsCritical(): void
    {
        $queues = (new FakeMessengerQueues())->withTransport('main', 0, null, 'main_failed')->withFailureTransport('main_failed', 101);

        self::assertSame(HealthStatus::Critical, $this->execute($queues)->status);
    }

    public function testATransportWithoutFailureTransportWarnsByName(): void
    {
        $queues = (new FakeMessengerQueues())
            ->withTransport('main', 0, null, 'main_failed')
            ->withFailureTransport('main_failed', 0)
            ->withTransport('async', 0)
        ;

        $result = $this->execute($queues);

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.data.messenger_failed.no_failure_transport', ['%transports%' => 'async']);
        self::assertEquals(DocumentationLink::create(), $result->action);
    }

    public function testFailedMessagesAreShownFirstAndTheUnprotectedTransportsInTheDetail(): void
    {
        $queues = (new FakeMessengerQueues())
            ->withTransport('main', 0, null, 'main_failed')
            ->withFailureTransport('main_failed', 2)
            ->withTransport('async', 0)
        ;

        $result = $this->execute($queues);

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.data.messenger_failed.failed', ['%count%' => 2]);
        self::assertDetail($result, 'tmonier_platform_health.data.messenger_failed.no_failure_transport', ['%transports%' => 'async']);
    }

    public function testAFailureTransportThatCannotCountIsUnknown(): void
    {
        $queues = (new FakeMessengerQueues())->withTransport('main', 0, null, 'main_failed')->withFailureTransport('main_failed', null);

        self::assertSummary($this->execute($queues), HealthStatus::Unknown, 'tmonier_platform_health.data.messenger_failed.not_countable', ['%transport%' => 'main_failed']);
    }

    public function testWithoutAsynchronousTransportTheProbeSaysSo(): void
    {
        self::assertSummary($this->execute(new FakeMessengerQueues()), HealthStatus::Ok, 'tmonier_platform_health.data.messenger.no_transport');
    }

    private function execute(FakeMessengerQueues $queues): HealthResult
    {
        return (new MessengerFailedCheck($queues, 100))->run();
    }
}
