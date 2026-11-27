<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Http;

/**
 * What a request of the public entry point is allowed to read.
 */
enum AccessLevel
{
    /** The detail token: the checks, one line each. */
    case Detail;

    /** The token: the overall status. */
    case Global;

    /** No token, liveness enabled: the HTTP code only. */
    case Liveness;
}
