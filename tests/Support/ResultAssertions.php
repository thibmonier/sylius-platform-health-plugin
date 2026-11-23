<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support;

use PHPUnit\Framework\Assert;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthResult;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthStatus;

trait ResultAssertions
{
    /**
     * @param array<string, scalar> $parameters the expected subset of the parameters of the summary
     */
    private static function assertSummary(HealthResult $result, HealthStatus $status, string $key, array $parameters = []): void
    {
        Assert::assertSame($status, $result->status);
        self::assertMessage($result->summary, $key, $parameters);
    }

    /**
     * @param array<string, scalar> $parameters
     */
    private static function assertDetail(HealthResult $result, string $key, array $parameters = []): void
    {
        Assert::assertNotNull($result->detail, 'The result should carry a detail.');
        self::assertMessage($result->detail, $key, $parameters);
    }

    /**
     * @param array<string, scalar> $parameters
     */
    private static function assertMessage(TranslatableInterface $message, string $key, array $parameters): void
    {
        Assert::assertInstanceOf(TranslatableMessage::class, $message);
        Assert::assertSame($key, $message->getMessage());
        foreach ($parameters as $name => $value) {
            Assert::assertArrayHasKey($name, $message->getParameters());
            Assert::assertSame($value, $message->getParameters()[$name]);
        }
    }

    private static function serialized(HealthResult $result): string
    {
        $parts = [];
        foreach ([$result->summary, $result->detail] as $message) {
            if ($message instanceof TranslatableMessage) {
                $parts[] = $message->getMessage() . json_encode($message->getParameters(), \JSON_THROW_ON_ERROR);
            }
        }

        return implode(' ', $parts);
    }
}
