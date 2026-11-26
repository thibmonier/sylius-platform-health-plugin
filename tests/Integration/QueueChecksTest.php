<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Tmonier\SyliusPlatformHealthPlugin\Data\MessengerQueuesInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCheckRunner;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

/**
 * The queue probes on the Messenger transports Sylius declares (Doctrine transports, each with its failure transport).
 */
final class QueueChecksTest extends KernelTestCase
{
    private const PROBES = [
        'data.messenger_pending' => HealthCategory::Data,
        'data.messenger_oldest' => HealthCategory::Data,
        'data.messenger_failed' => HealthCategory::Data,
        'environment.cache_pool' => HealthCategory::Environment,
        'environment.writable_dirs' => HealthCategory::Environment,
    ];

    public function testThePluginShipsTheFiveProbesAndTheyAreOkOnTheTestApplication(): void
    {
        $reports = [];
        foreach ($this->runner()->run() as $report) {
            $reports[$report->identifier] = $report;
        }

        foreach (self::PROBES as $identifier => $category) {
            self::assertArrayHasKey($identifier, $reports);
            self::assertSame($category, $reports[$identifier]->category, $identifier);
            self::assertSame(HealthStatus::Ok, $reports[$identifier]->result->status, $identifier);
        }
    }

    public function testTheQueuesAreTheConsumableTransportsOfSyliusWithTheirFailureTransports(): void
    {
        $queues = $this->queues();

        self::assertContains('main', $queues->consumableTransports());
        self::assertNotContains('main_failed', $queues->consumableTransports(), 'A failure transport is not a queue a worker consumes.');
        self::assertSame('main_failed', $queues->failureTransportOf('main'));
        self::assertSame(0, $queues->countMessages('main_failed'));
    }

    public function testAMessageWaitingInADoctrineTransportIsCountedAndDated(): void
    {
        $queues = $this->queues();
        $transport = self::getContainer()->get('tmonier_sylius_platform_health.test.transport.main');
        self::assertInstanceOf(TransportInterface::class, $transport);
        // The database of the test application is kept between runs: the queue starts empty, so its oldest message is ours.
        $this->drain($transport);
        self::assertSame(0, $queues->countMessages('main'));

        $transport->send(new Envelope(new \stdClass()));

        try {
            self::assertSame(1, $queues->countMessages('main'));
            $oldest = $queues->oldestMessage('main');
            self::assertTrue($oldest->measurable);
            self::assertNotNull($oldest->waitingSince);
            self::assertLessThanOrEqual(120, abs((new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->getTimestamp() - $oldest->waitingSince->getTimestamp()));
        } finally {
            $this->drain($transport);
        }
    }

    private function drain(TransportInterface $transport): void
    {
        do {
            $envelopes = [...$transport->get()];
            foreach ($envelopes as $envelope) {
                $transport->reject($envelope);
            }
        } while ([] !== $envelopes);
    }

    private function runner(): HealthCheckRunner
    {
        self::bootKernel();
        $runner = self::getContainer()->get('tmonier_sylius_platform_health.test.runner');
        self::assertInstanceOf(HealthCheckRunner::class, $runner);

        return $runner;
    }

    private function queues(): MessengerQueuesInterface
    {
        self::bootKernel();
        $queues = self::getContainer()->get('tmonier_sylius_platform_health.test.data.messenger_queues');
        self::assertInstanceOf(MessengerQueuesInterface::class, $queues);

        return $queues;
    }
}
