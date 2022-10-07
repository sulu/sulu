<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authentication\RoleRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class RoleRepositoryTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var RoleRepositoryInterface
     */
    private $roleRepository;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();
        $this->roleRepository = $this->client->getContainer()->get('sulu.repository.role');
    }

    public function testFindRoleIdsBySystem(): void
    {
        $role1 = $this->createRole('Role 1', 'Sulu');
        $role2 = $this->createRole('Role 2', 'Website');
        $role3 = $this->createRole('Role 3', 'Sulu');
        $this->em->flush();

        $suluRoleIds = $this->roleRepository->findRoleIdsBySystem('Sulu');
        $websiteRoleIds = $this->roleRepository->findRoleIdsBySystem('Website');

        $this->assertCount(2, $suluRoleIds);
        $this->assertContains($role1->getId(), $suluRoleIds);
        $this->assertContains($role3->getId(), $suluRoleIds);

        $this->assertCount(1, $websiteRoleIds);
        $this->assertContains($role2->getId(), $websiteRoleIds);
    }

    private function createRole(string $name, string $system)
    {
        $role = new Role();
        $role->setName($name);
        $role->setSystem($system);

        $this->em->persist($role);

        return $role;
    }
}
