<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Tmonier\SyliusPlatformHealthPlugin\Security\PlatformHealthVoter;
use Twig\Environment;

#[IsGranted(PlatformHealthVoter::VIEW)]
final readonly class HealthPageController
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    public function __invoke(): Response
    {
        return new Response($this->twig->render('@TmonierSyliusPlatformHealthPlugin/admin/index.html.twig'));
    }
}
