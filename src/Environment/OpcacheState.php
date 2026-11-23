<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

enum OpcacheState: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Restricted = 'restricted';
}
