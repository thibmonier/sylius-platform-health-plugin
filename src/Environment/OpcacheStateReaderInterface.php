<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

interface OpcacheStateReaderInterface
{
    public function read(): OpcacheState;
}
