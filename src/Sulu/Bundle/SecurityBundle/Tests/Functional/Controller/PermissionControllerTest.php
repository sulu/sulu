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

use Coduo\PHPMatcher\PHPUnit\PHPMatcherAssertions;
use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class PermissionControllerTest extends SuluTestCase
{
    use PHPMatcherAssertions;

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

        $this->assertMatchesPattern(<<<JSON
            {
                "permissions": {
                    "{$role1->getId()}": {
                        "add": false,
                        "archive": false,
                        "delete": false,
                        "edit": true,
                        "live": false,
                        "security": false,
                        "view": true
                    },
                    "{$role2->getId()}": {
                        "add": false,
                        "archive": false,
                        "delete": false,
                        "edit": true,
                        "live": false,
                        "security": false,
                        "view": true
                    }
                }
            }
            JSON,
            $this->client->getResponse()->getContent() ?: ''
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

        $this->assertMatchesPattern(<<<JSON
            {
                "permissions": {
                    "{$role1->getId()}": {
                        "add": false,
                        "archive": false,
                        "delete": false,
                        "edit": false,
                        "live": false,
                        "security": false,
                        "view": true
                    }
                }
            }
            JSON,
            $this->client->getResponse()->getContent() ?: ''
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
