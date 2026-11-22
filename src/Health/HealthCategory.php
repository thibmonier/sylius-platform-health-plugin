<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Health;

enum HealthCategory: string
{
    case Environment = 'environment';
    case Data = 'data';
    case ScheduledTasks = 'scheduled_tasks';
    case Shop = 'shop';
    case Plugins = 'plugins';
}
