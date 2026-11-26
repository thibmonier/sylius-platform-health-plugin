<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Data;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\FakeMessengerQueues;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Data\MessengerOldestCheck;
use Tmonier\SyliusPlatformHealthPlugin\Data\OldestMessage;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class MessengerOldestCheckTest extends TestCase
{
    use ResultAssertions;

    private const NOW = '2026-11-26 12:00:00';

    public function testEmptyQueuesAreOk(): void
    {
        self::assertSummary($this->execute((new FakeMessengerQueues())->withTransport('async')), HealthStatus::Ok, 'tmonier_platform_health.data.messenger_oldest.ok', ['%minutes%' => 15]);
    }

    public function testAMessageWaitingLessThanTheThresholdIsOk(): void
    {
        self::assertSame(HealthStatus::Ok, $this->execute($this->waitingFor('-10 minutes'))->status);
    }

    public function testAMessageWaitingBeyondTheThresholdWarns(): void
    {
        $result = $this->execute($this->waitingFor('-20 minutes'));

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.data.messenger_oldest.late', ['%transport%' => 'async', '%minutes%' => 20]);
    }

    public function testAStoppedConsumerIsCriticalWithTheTransportAndTheAgeAndTheDocumentation(): void
    {
        $result = $this->execute($this->waitingFor('-2 hours'));

        self::assertSummary($result, HealthStatus::Critical, 'tmonier_platform_health.data.messenger_oldest.late', ['%transport%' => 'async', '%minutes%' => 120]);
        self::assertEquals(DocumentationLink::create(), $result->action);
    }

    public function testTheOldestMessageOfAllTransportsIsTheOneShown(): void
    {
        $queues = (new FakeMessengerQueues())
            ->withTransport('async', 1, OldestMessage::waitingSince(new \DateTimeImmutable(self::NOW . ' -20 minutes')))
            ->withTransport('main', 1, OldestMessage::waitingSince(new \DateTimeImmutable(self::NOW . ' -90 minutes')))
        ;

        self::assertSummary($this->execute($queues), HealthStatus::Critical, 'tmonier_platform_health.data.messenger_oldest.late', ['%transport%' => 'main']);
    }

    public function testWithoutADoctrineTransportTheAgeIsUnknownNeverOk(): void
    {
        $result = $this->execute((new FakeMessengerQueues())->withTransport('amqp', 4, OldestMessage::notMeasurable()));

        self::assertSummary($result, HealthStatus::Unknown, 'tmonier_platform_health.data.messenger_oldest.not_measurable');
    }

    public function testWithoutAsynchronousTransportTheProbeSaysSo(): void
    {
        self::assertSummary($this->execute(new FakeMessengerQueues()), HealthStatus::Ok, 'tmonier_platform_health.data.messenger.no_transport');
    }

    private function waitingFor(string $age): FakeMessengerQueues
    {
        return (new FakeMessengerQueues())->withTransport('async', 1, OldestMessage::waitingSince(new \DateTimeImmutable(self::NOW . ' ' . $age)));
    }

    private function execute(FakeMessengerQueues $queues): HealthResult
    {
        return (new MessengerOldestCheck($queues, new MockClock(self::NOW), 15, 60))->run();
    }
}
