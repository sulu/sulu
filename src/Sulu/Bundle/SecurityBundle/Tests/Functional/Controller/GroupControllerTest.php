<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Controller;

use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class GroupControllerTest extends SuluTestCase
{
    /**
     * @var Role
     */
    protected $role1;

    /**
     * @var Role
     */
    protected $role2;

    /**
     * @var Group
     */
    protected $group1;

    /**
     * @var Group
     */
    protected $group2;

    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();

        $datetime = new \DateTime();

        $role1 = new Role();
        $role1->setName('Sulu Administrator');
        $role1->setSystem('Sulu');
        $this->em->persist($role1);
        $this->role1 = $role1;

        $role2 = new Role();
        $role2->setName('Sulu Manager');
        $role2->setSystem('Sulu');
        $this->em->persist($role2);
        $this->role2 = $role2;

        $group1 = new Group();
        $group1->setName('Group1');
        $group1->addRole($role1);
        $group1->addRole($role2);
        $this->em->persist($group1);
        $this->group1 = $group1;

        $group2 = new Group();
        $group2->setName('Group2');
        $group2->addRole($role1);
        $this->em->persist($group2);
        $this->group2 = $group2;

        $this->em->flush();
    }

    public function testList()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/groups?flat=true');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertEquals('Group1', $response->_embedded->groups[0]->name);
        $this->assertEquals('Group2', $response->_embedded->groups[1]->name);
    }

    public function testGetById()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/groups/' . $this->group1->getId());
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Group1', $response->name);
        $this->assertCount(2, $response->roles);
        $this->assertEquals('Sulu Administrator', $response->roles[0]->name);
        $this->assertEquals('Sulu Manager', $response->roles[1]->name);
    }

    public function testPost()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'POST',
            '/api/groups',
            [
                'name' => 'Group3',
                'parent' => ['id' => $this->group1->getId()],
                'roles' => [
                    [
                        'id' => $this->role1->getId(),
                    ],
                    [
                        'id' => $this->role2->getId(),
                    ],
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Group3', $response->name);
        $this->assertEquals($this->group1->getId(), $response->parent->id);
        $this->assertCount(2, $response->roles);
        $this->assertEquals('Sulu Administrator', $response->roles[0]->name);
        $this->assertEquals('Sulu Manager', $response->roles[1]->name);

        $client->request(
            'GET',
            '/api/groups/' . $response->id
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Group3', $response->name);
        $this->assertEquals('Group1', $response->parent->name);
        $this->assertCount(2, $response->roles);
        $this->assertEquals('Sulu Administrator', $response->roles[0]->name);
        $this->assertEquals('Sulu Manager', $response->roles[1]->name);
    }

    public function testPut()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/groups/' . $this->group1->getId(),
            [
                'name' => 'Updated Group1',
                'parent' => ['id' => $this->group2->getId()],
                'roles' => [
                    [
                        'id' => $this->role1->getId(),
                    ],
                ],
            ]
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Updated Group1', $response->name);
        $this->assertEquals($this->group2->getId(), $response->parent->id);
        $this->assertCount(1, $response->roles);
        $this->assertEquals('Sulu Administrator', $response->roles[0]->name);

        $client->request('GET', '/api/groups/' . $this->group1->getId());

        $this->assertEquals('Updated Group1', $response->name);
        $this->assertEquals($this->group2->getId(), $response->parent->id);
        $this->assertCount(1, $response->roles);
        $this->assertEquals('Sulu Administrator', $response->roles[0]->name);
    }

    public function testDelete()
    {
        $client = $this->createAuthenticatedClient();

        $client->request(
            'GET',
            '/api/groups'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals(2, count($response->_embedded->groups));

        $client->request(
            'DELETE',
            '/api/groups/' . $this->group1->getId()
        );

        $this->assertHttpStatusCode(204, $client->getResponse());

        $client->request(
            'GET',
            '/api/groups'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals(1, count($response->_embedded->groups));
    }
}
