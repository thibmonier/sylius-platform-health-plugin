<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Twig;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tmonier\SyliusPlatformHealthPlugin\Health\HealthActionLink;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Turns the route of an action link into a URL. A probe that points to a route that does not exist (or whose
 * parameters do not fit) gets no link, never an error: the page stays readable.
 */
final class ActionUrlExtension extends AbstractExtension
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('tmonier_platform_health_action_url', $this->actionUrl(...))];
    }

    public function actionUrl(?HealthActionLink $link): ?string
    {
        if (!$link instanceof HealthActionLink) {
            return null;
        }

        try {
            return $this->urlGenerator->generate($link->route, $link->parameters);
        } catch (ExceptionInterface $exception) {
            $this->logger->warning('The action link of a health check cannot be generated.', ['route' => $link->route, 'exception' => $exception]);

            return null;
        }
    }
}
