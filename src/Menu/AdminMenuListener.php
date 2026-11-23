<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Menu;

use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Tmonier\SyliusPlatformHealthPlugin\Security\PlatformHealthVoter;

final readonly class AdminMenuListener
{
    public const ROUTE = 'tmonier_platform_health_admin_index';

    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function __invoke(MenuBuilderEvent $event): void
    {
        if (!$this->authorizationChecker->isGranted(PlatformHealthVoter::VIEW)) {
            return;
        }

        $configuration = $event->getMenu()->getChild('configuration');
        if (!$configuration instanceof ItemInterface) {
            return;
        }

        $configuration
            ->addChild('tmonier_platform_health', ['route' => self::ROUTE])
            ->setLabel('tmonier_platform_health.ui.menu')
            ->setLabelAttribute('icon', 'tabler:heart-rate-monitor')
        ;
    }
}
