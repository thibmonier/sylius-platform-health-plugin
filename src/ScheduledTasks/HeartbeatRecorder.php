<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\ScheduledTasks;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Records the end of each declared command. Symfony dispatches TERMINATE after ERROR too, with the exit code of the
 * failure: one listener sees successes and failures. A failure to record is logged; the exit code of the command stays.
 */
final readonly class HeartbeatRecorder implements EventSubscriberInterface
{
    /**
     * @param list<string> $commands the declared commands, the only ones recorded
     */
    public function __construct(
        private HeartbeatStoreInterface $store,
        private ClockInterface $clock,
        private LoggerInterface $logger,
        private array $commands,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [ConsoleEvents::TERMINATE => 'onTerminate'];
    }

    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand()?->getName();
        if (null === $command || !\in_array($command, $this->commands, true)) {
            return;
        }

        try {
            $this->store->record($command, $event->getExitCode(), $this->clock->now());
        } catch (\Throwable $exception) {
            // The heartbeat must never break the command it watches: logged, and the exit code stays the command's own.
            $this->logger->error('The heartbeat of "{command}" could not be recorded.', ['command' => $command, 'exception' => $exception]);
        }
    }
}
