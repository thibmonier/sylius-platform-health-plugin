<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\ScheduledTasks;

use PHPUnit\Framework\TestCase;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthCategory;
use Tmonier\SyliusPlatformHealthPlugin\ScheduledTasks\HeartbeatCheck;

final class HeartbeatContractTest extends TestCase
{
    public function testTheIdentifierIsAConstantOfTheScheduledTasksCategory(): void
    {
        $check = (new \ReflectionClass(HeartbeatCheck::class))->newInstanceWithoutConstructor();

        self::assertSame('scheduled_tasks.heartbeat', $check->identifier());
        self::assertSame(HealthCategory::ScheduledTasks, $check->category());
    }
}
