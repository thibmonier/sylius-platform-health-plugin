<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Decides the PLATFORM_HEALTH_VIEW attribute: the one role the integrator configured.
 *
 * @extends Voter<string, null>
 */
final class PlatformHealthVoter extends Voter
{
    public const VIEW = 'PLATFORM_HEALTH_VIEW';

    public function __construct(
        private readonly RoleHierarchyInterface $roleHierarchy,
        private readonly string $role,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::VIEW === $attribute;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return \in_array($this->role, $this->roleHierarchy->getReachableRoleNames($token->getRoleNames()), true);
    }
}
