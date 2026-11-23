<?php

declare(strict_types=1);

namespace Tmonier\SyliusPlatformHealthPlugin\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * Decides the PLATFORM_HEALTH_VIEW attribute: the one role the integrator configured.
 *
 * Implements the interface rather than extending Voter: the signature of Voter::voteOnAttribute() differs between
 * Symfony 6.4 and 8, the one of vote() accepts an optional fourth argument on both.
 */
final readonly class PlatformHealthVoter implements VoterInterface
{
    public const VIEW = 'PLATFORM_HEALTH_VIEW';

    public function __construct(
        private RoleHierarchyInterface $roleHierarchy,
        private string $role,
    ) {
    }

    /**
     * @param array<mixed> $attributes
     */
    public function vote(TokenInterface $token, mixed $subject, array $attributes, mixed $vote = null): int
    {
        if (!\in_array(self::VIEW, $attributes, true)) {
            return self::ACCESS_ABSTAIN;
        }

        return \in_array($this->role, $this->roleHierarchy->getReachableRoleNames($token->getRoleNames()), true)
            ? self::ACCESS_GRANTED
            : self::ACCESS_DENIED;
    }
}
