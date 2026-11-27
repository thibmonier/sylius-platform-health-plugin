<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Http;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthSnapshotProvider;

/**
 * The public entry point. It answers like an absent page to anyone who has no valid token (and while no token is configured),
 * counts every request per IP address before doing anything else, and reads the cached snapshot: it never runs the probes
 * on its own. 503 only when a check is critical, so that a "warning" or an "unknown" never takes a host's check down.
 */
final readonly class HealthEndpointController
{
    private PublicReport $report;

    public function __construct(
        private HealthSnapshotProvider $snapshots,
        private RateLimiterFactory $limiter,
        private ?string $token,
        private ?string $detailToken,
        private bool $liveness,
    ) {
        $this->report = new PublicReport();
    }

    public function __invoke(Request $request): Response
    {
        if (null === $this->token && null === $this->detailToken) {
            throw new NotFoundHttpException();
        }

        $limit = $this->limiter->create($request->getClientIp() ?? 'unknown')->consume();
        if (!$limit->isAccepted()) {
            return $this->response('', Response::HTTP_TOO_MANY_REQUESTS, ['Retry-After' => (string) max(1, $limit->getRetryAfter()->getTimestamp() - time())]);
        }

        $level = $this->levelOf($request);
        $snapshot = $this->snapshots->snapshot();
        $code = $this->report->hasCriticalCheck($snapshot) ? Response::HTTP_SERVICE_UNAVAILABLE : Response::HTTP_OK;

        return match ($level) {
            AccessLevel::Liveness => $this->response('', $code),
            AccessLevel::Global => $this->json($this->report->global($snapshot), $code),
            AccessLevel::Detail => $this->json($this->report->detail($snapshot), $code),
        };
    }

    private function levelOf(Request $request): AccessLevel
    {
        if (!$request->headers->has('Authorization')) {
            return $this->liveness && 0 === $request->query->count() ? AccessLevel::Liveness : throw new NotFoundHttpException();
        }

        $bearer = $this->bearer($request);
        if (null !== $bearer && null !== $this->detailToken && hash_equals($this->detailToken, $bearer)) {
            return AccessLevel::Detail;
        }
        if (null !== $bearer && null !== $this->token && hash_equals($this->token, $bearer)) {
            return AccessLevel::Global;
        }

        throw new NotFoundHttpException();
    }

    private function bearer(Request $request): ?string
    {
        if (1 !== preg_match('/^Bearer (\S+)$/', (string) $request->headers->get('Authorization'), $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function json(array $payload, int $code): Response
    {
        return $this->response(json_encode($payload, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES), $code, ['Content-Type' => 'application/json']);
    }

    /**
     * @param array<string, string> $headers
     */
    private function response(string $content, int $code, array $headers = []): Response
    {
        return new Response($content, $code, $headers + ['Cache-Control' => 'no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
