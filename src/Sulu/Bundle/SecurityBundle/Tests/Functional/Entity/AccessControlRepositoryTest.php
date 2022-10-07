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
use Sulu\Bundle\SecurityBundle\Entity\AccessControl;
use Sulu\Bundle\SecurityBundle\Entity\AccessControlRepository;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class AccessControlRepositoryTest extends SuluTestCase
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
     * @var AccessControlRepository
     */
    private $accessControlRepository;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();
        $this->accessControlRepository = $this->client->getContainer()->get('sulu.repository.access_control');
    }

    public function testFindByTypeAndId(): void
    {
        $role1 = $this->createRole('Role 1', 'Sulu');
        $accessControl1 = $this->createAccessControl(1, 'Some\\Class', $role1);

        $role2 = $this->createRole('Role 2', 'Website');
        $accessControl2 = $this->createAccessControl(1, 'Some\\Class', $role2);

        $role3 = $this->createRole('Role 3', 'Sulu');
        $accessControl3 = $this->createAccessControl(1, 'Some\\Class', $role3);

        $this->em->flush();

        $allAccessControls = $this->accessControlRepository->findByTypeAndId('Some\\Class', 1);
        $this->assertCount(3, $allAccessControls);
        $this->assertContains($accessControl1, $allAccessControls);
        $this->assertContains($accessControl2, $allAccessControls);
        $this->assertContains($accessControl3, $allAccessControls);

        $suluAccessControls = $this->accessControlRepository->findByTypeAndId('Some\\Class', 1, 'Sulu');
        $this->assertCount(2, $suluAccessControls);
        $this->assertContains($accessControl1, $suluAccessControls);
        $this->assertContains($accessControl3, $suluAccessControls);

        $websiteAccessControls = $this->accessControlRepository->findByTypeAndId('Some\\Class', 1, 'Website');
        $this->assertCount(1, $websiteAccessControls);
        $this->assertContains($accessControl2, $websiteAccessControls);
    }

    private function createAccessControl($entityId, string $entityClass, Role $role)
    {
        $accessControl = new AccessControl();
        $accessControl->setPermissions(127);
        $accessControl->setEntityId($entityId);
        $accessControl->setEntityClass($entityClass);
        $accessControl->setRole($role);
        $this->em->persist($accessControl);

        return $accessControl;
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
