<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthSnapshotProvider;

/**
 * The public entry point. Red stub of US-308: answers 501 until the real implementation.
 */
final readonly class HealthEndpointController
{
    public function __construct(
        public HealthSnapshotProvider $snapshots,
        public RateLimiterFactory $limiter,
        public ?string $token,
        public ?string $detailToken,
        public bool $liveness,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        return new Response('', Response::HTTP_NOT_IMPLEMENTED);
    }
}
