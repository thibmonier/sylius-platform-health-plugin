<?php

declare(strict_types=1);

namespace Tests\Tmonier\SyliusPlatformHealthPlugin\Support;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Resource\Factory\FactoryInterface;

/**
 * Administrators with the exact roles of the scenario, created for the test and removed after it
 * (the user is reloaded from the database at each request, so the roles must be persisted).
 */
trait CreatesAdministrators
{
    /** @var list<AdminUser> */
    protected array $createdAdministrators = [];

    /**
     * @param list<string> $roles
     */
    protected function createAdministrator(array $roles): AdminUser
    {
        $factory = self::getContainer()->get('sylius.factory.admin_user');
        self::assertInstanceOf(FactoryInterface::class, $factory);
        $administrator = $factory->createNew();
        self::assertInstanceOf(AdminUser::class, $administrator);

        $suffix = bin2hex(random_bytes(4));
        $administrator->setUsername('health-' . $suffix);
        $administrator->setEmail(sprintf('health-%s@example.com', $suffix));
        $administrator->setPlainPassword(bin2hex(random_bytes(12)));
        $administrator->setLocaleCode('en_US');
        $administrator->setEnabled(true);
        $administrator->removeRole(AdminUser::DEFAULT_ADMIN_ROLE);
        foreach ($roles as $role) {
            $administrator->addRole($role);
        }

        $manager = $this->entityManager();
        $manager->persist($administrator);
        $manager->flush();
        $this->createdAdministrators[] = $administrator;

        return $administrator;
    }

    protected function removeAdministrators(): void
    {
        $manager = $this->entityManager();
        foreach ($this->createdAdministrators as $administrator) {
            $persisted = $manager->find(AdminUser::class, $administrator->getId());
            if (null !== $persisted) {
                $manager->remove($persisted);
            }
        }
        $manager->flush();
        $this->createdAdministrators = [];
    }

    protected function entityManager(): EntityManagerInterface
    {
        $manager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }
}
