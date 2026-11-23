<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Environment;

/**
 * Red stub (US-293).
 */
final class NativeOpcacheStateReader implements OpcacheStateReaderInterface
{
    public function read(): OpcacheState
    {
        return OpcacheState::Active;
    }
}
