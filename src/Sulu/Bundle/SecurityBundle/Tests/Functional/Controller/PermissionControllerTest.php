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
use Sulu\Component\DocumentManager\DocumentManagerInterface;
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

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->em = $this->getEntityManager();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->purgeDatabase();
        $this->initPhpcr();
    }

    public function testCputWithDoctrine(): void
    {
        $role1 = $this->createRole('Role 1');
        $role2 = $this->createRole('Role 2');
        $this->em->flush();
        $this->em->clear();

        $this->client->jsonRequest(
            'PUT',
            '/api/permissions?resourceKey=secured_entity&id=2',
            [
                'permissions' => [
                    $role1->getId() => ['view' => 'true', 'edit' => 'true'],
                    $role2->getId() => ['view' => 'true', 'edit' => 'true'],
                ],
            ]
        );

        $this->client->jsonRequest(
            'GET',
            '/api/permissions?resourceKey=secured_entity&id=2'
        );

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $response['permissions']);
        $this->assertEquals(
            [
                'view' => true,
                'add' => false,
                'edit' => true,
                'delete' => false,
                'archive' => false,
                'archive' => false,
                'security' => false,
                'live' => false,
            ],
            $response['permissions'][$role1->getId()]
        );
        $this->assertEquals(
            [
                'view' => true,
                'add' => false,
                'edit' => true,
                'delete' => false,
                'archive' => false,
                'archive' => false,
                'security' => false,
                'live' => false,
            ],
            $response['permissions'][$role2->getId()]
        );

        $this->client->request(
            'PUT',
            '/api/permissions?resourceKey=secured_entity&id=2',
            [
                'permissions' => [
                    $role1->getId() => ['view' => 'true', 'edit' => 'false'],
                ],
            ]
        );

        $this->client->request(
            'GET',
            '/api/permissions?resourceKey=secured_entity&id=2'
        );

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['permissions']);
        $this->assertEquals(
            [
                'view' => true,
                'add' => false,
                'edit' => false,
                'delete' => false,
                'archive' => false,
                'archive' => false,
                'security' => false,
                'live' => false,
            ],
            $response['permissions'][$role1->getId()]
        );
    }

    public function testCputWithPhpcr(): void
    {
        $role1 = $this->createRole('Role 1');
        $role2 = $this->createRole('Role 2');
        $this->em->flush();
        $this->em->clear();

        $document = $this->documentManager->create('secured_document');
        $document->setTitle('Test');
        $this->documentManager->persist($document, 'en', ['parent_path' => '/cmf/sulu_io/contents']);
        $this->documentManager->flush();

        $this->documentManager->clear();

        $this->client->jsonRequest(
            'PUT',
            '/api/permissions?resourceKey=secured_document&id=' . $document->getUuid(),
            [
                'permissions' => [
                    $role1->getId() => ['view' => 'true', 'edit' => 'true'],
                    $role2->getId() => ['view' => 'true', 'edit' => 'true'],
                ],
            ]
        );

        $this->client->jsonRequest(
            'GET',
            '/api/permissions?resourceKey=secured_document&id=' . $document->getUuid()
        );

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $response['permissions']);
        $this->assertEquals(
            [
                'view' => true,
                'add' => false,
                'edit' => true,
                'delete' => false,
                'archive' => false,
                'archive' => false,
                'security' => false,
                'live' => false,
            ],
            $response['permissions'][$role1->getId()]
        );
        $this->assertEquals(
            [
                'view' => true,
                'add' => false,
                'edit' => true,
                'delete' => false,
                'archive' => false,
                'archive' => false,
                'security' => false,
                'live' => false,
            ],
            $response['permissions'][$role2->getId()]
        );

        $this->client->jsonRequest(
            'PUT',
            '/api/permissions?resourceKey=secured_document&id=' . $document->getUuid(),
            [
                'permissions' => [
                    $role1->getId() => ['view' => 'true', 'edit' => 'false'],
                ],
            ]
        );

        $this->client->jsonRequest(
            'GET',
            '/api/permissions?resourceKey=secured_document&id=' . $document->getUuid()
        );

        $response = \json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $response['permissions']);
        $this->assertEquals(
            [
                'view' => true,
                'add' => false,
                'edit' => false,
                'delete' => false,
                'archive' => false,
                'archive' => false,
                'security' => false,
                'live' => false,
            ],
            $response['permissions'][$role1->getId()]
        );
    }

    private function createRole(string $name)
    {
        $role = new Role();
        $role->setName($name);
        $role->setSystem('Sulu');

        $this->em->persist($role);

        return $role;
    }
}
