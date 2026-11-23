<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Unit\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Tmonier\SyliusPlatformHealthPlugin\Security\PlatformHealthVoter;

final class PlatformHealthVoterTest extends TestCase
{
    public function testItGrantsTheAdministratorWhoCarriesTheConfiguredRole(): void
    {
        $voter = $this->voter('ROLE_PLATFORM_HEALTH_VIEW');

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token(['ROLE_ADMINISTRATION_ACCESS', 'ROLE_PLATFORM_HEALTH_VIEW']), null, [PlatformHealthVoter::VIEW]));
    }

    public function testItDeniesTheAdministratorWithoutTheConfiguredRole(): void
    {
        $voter = $this->voter('ROLE_PLATFORM_HEALTH_VIEW');

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token(['ROLE_ADMINISTRATION_ACCESS']), null, [PlatformHealthVoter::VIEW]));
    }

    public function testTheDefaultRoleLetsEveryAdministratorIn(): void
    {
        $voter = $this->voter('ROLE_ADMINISTRATION_ACCESS');

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token(['ROLE_ADMINISTRATION_ACCESS']), null, [PlatformHealthVoter::VIEW]));
    }

    public function testTheDefaultRoleStillDeniesAUserWhoIsNotAnAdministrator(): void
    {
        $voter = $this->voter('ROLE_ADMINISTRATION_ACCESS');

        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($this->token(['ROLE_USER']), null, [PlatformHealthVoter::VIEW]));
    }

    public function testItFollowsTheRoleHierarchyOfTheApplication(): void
    {
        $voter = $this->voter('ROLE_PLATFORM_HEALTH_VIEW', ['ROLE_SUPER_ADMIN' => ['ROLE_PLATFORM_HEALTH_VIEW']]);

        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($this->token(['ROLE_SUPER_ADMIN']), null, [PlatformHealthVoter::VIEW]));
    }

    public function testItAbstainsOnAnyOtherAttribute(): void
    {
        $voter = $this->voter('ROLE_PLATFORM_HEALTH_VIEW');

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($this->token(['ROLE_PLATFORM_HEALTH_VIEW']), null, ['SOMETHING_ELSE']));
    }

    /**
     * @param array<string, list<string>> $hierarchy
     */
    private function voter(string $role, array $hierarchy = []): PlatformHealthVoter
    {
        return new PlatformHealthVoter(new RoleHierarchy($hierarchy), $role);
    }

    /**
     * @param list<string> $roles
     */
    private function token(array $roles): TokenInterface
    {
        return new UsernamePasswordToken(new InMemoryUser('admin', null, $roles), 'admin', $roles);
    }
}
