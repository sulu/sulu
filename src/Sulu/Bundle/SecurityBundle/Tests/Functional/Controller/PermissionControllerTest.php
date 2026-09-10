<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class PermissionControllerTest extends SuluTestCase
{
    /**
     * @var KernelBrowser
     */
    private $client;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();
    }

    public function testCputWithDoctrine(): void
    {
        $role1 = $this->createRole('Role 1');
        $role2 = $this->createRole('Role 2');
        $this->em->flush();
        $this->em->clear();

        /** @var int $role1Id */
        $role1Id = $role1->getId();
        /** @var int $role2Id */
        $role2Id = $role2->getId();

        $this->client->jsonRequest(
            'PUT',
            '/api/permissions?resourceKey=secured_entity&id=2',
            [
                'permissions' => [
                    $role1Id => ['view' => 'true', 'edit' => 'true'],
                    $role2Id => ['view' => 'true', 'edit' => 'true'],
                ],
            ]
        );

        $this->client->jsonRequest(
            'GET',
            '/api/permissions?resourceKey=secured_entity&id=2'
        );

        $response = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('permissions', $response);
        /** @var array<int, array<string, bool>> $permissions */
        $permissions = $response['permissions'];
        $this->assertCount(2, $permissions);
        $this->assertEquals(
            [
                'view' => true,
                'add' => false,
                'edit' => true,
                'delete' => false,
                'archive' => false,
                'security' => false,
                'live' => false,
                'review' => false,
            ],
            $permissions[$role1Id]
        );
        $this->assertEquals(
            [
                'view' => true,
                'add' => false,
                'edit' => true,
                'delete' => false,
                'archive' => false,
                'security' => false,
                'live' => false,
                'review' => false,
            ],
            $permissions[$role2Id]
        );

        $this->client->request(
            'PUT',
            '/api/permissions?resourceKey=secured_entity&id=2',
            [
                'permissions' => [
                    $role1Id => ['view' => 'true', 'edit' => 'false'],
                ],
            ]
        );

        $this->client->request(
            'GET',
            '/api/permissions?resourceKey=secured_entity&id=2'
        );

        $response = \json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('permissions', $response);
        /** @var array<int, array<string, bool>> $permissions2 */
        $permissions2 = $response['permissions'];
        $this->assertCount(1, $permissions2);
        $this->assertEquals(
            [
                'view' => true,
                'add' => false,
                'edit' => false,
                'delete' => false,
                'archive' => false,
                'security' => false,
                'live' => false,
                'review' => false,
            ],
            $permissions2[$role1Id]
        );
    }

    private function createRole(string $name): Role
    {
        $role = new Role();
        $role->setName($name);
        $role->setSystem('Sulu');

        $this->em->persist($role);

        return $role;
    }
}
