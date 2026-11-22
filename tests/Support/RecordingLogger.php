<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support;

use Psr\Log\AbstractLogger;

final class RecordingLogger extends AbstractLogger
{
    /** @var list<array{level: mixed, message: string, context: array<mixed>}> */
    public array $records = [];

    /**
     * @param array<mixed> $context
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->records[] = ['level' => $level, 'message' => (string) $message, 'context' => $context];
    }

    /**
     * @return list<\Throwable>
     */
    public function loggedExceptions(): array
    {
        $exceptions = [];
        foreach ($this->records as $record) {
            $exception = $record['context']['exception'] ?? null;
            if ($exception instanceof \Throwable) {
                $exceptions[] = $exception;
            }
        }

        return $exceptions;
    }
}
