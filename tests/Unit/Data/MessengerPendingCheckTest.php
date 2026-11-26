<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Data;

use PHPUnit\Framework\TestCase;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\FakeMessengerQueues;
use Tests\Tmonier\SyliusPlatformHealthPlugin\Support\ResultAssertions;
use Tmonier\SyliusPlatformHealthPlugin\Data\MessengerPendingCheck;
use Tmonier\SyliusPlatformHealthPlugin\Environment\DocumentationLink;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

final class MessengerPendingCheckTest extends TestCase
{
    use ResultAssertions;

    public function testAnApplicationWithoutAsynchronousTransportSaysSoAndIsOk(): void
    {
        self::assertSummary((new MessengerPendingCheck(new FakeMessengerQueues()))->run(), HealthStatus::Ok, 'tmonier_platform_health.data.messenger.no_transport');
    }

    public function testQueuesUnderTheThresholdAreOk(): void
    {
        $result = (new MessengerPendingCheck((new FakeMessengerQueues())->withTransport('async', 3)->withTransport('main', 0)))->run();

        self::assertSummary($result, HealthStatus::Ok, 'tmonier_platform_health.data.messenger_pending.ok', ['%threshold%' => 100]);
    }

    public function testTheFullestQueueBeyondTheThresholdWarnsWithItsName(): void
    {
        $result = (new MessengerPendingCheck((new FakeMessengerQueues())->withTransport('async', 150)->withTransport('main', 900), 100))->run();

        self::assertSummary($result, HealthStatus::Warning, 'tmonier_platform_health.data.messenger_pending.over', ['%transport%' => 'main', '%count%' => 900, '%threshold%' => 100]);
        self::assertEquals(DocumentationLink::create(), $result->action);
    }

    public function testATransportThatCannotCountIsUnknownNeverOk(): void
    {
        $result = (new MessengerPendingCheck((new FakeMessengerQueues())->withTransport('async', 3)->withTransport('amqp', null)))->run();

        self::assertSummary($result, HealthStatus::Unknown, 'tmonier_platform_health.data.messenger_pending.not_countable', ['%transport%' => 'amqp']);
    }
}
