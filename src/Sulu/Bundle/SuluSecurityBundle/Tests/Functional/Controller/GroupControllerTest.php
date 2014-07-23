<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Controller;


use Doctrine\ORM\Tools\SchemaTool;
use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;

class GroupControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    /**
     * @var SchemaTool
     */
    protected static $tool;

    public function setUp()
    {
        $this->setUpSchema();

        $datetime = new \DateTime();

        $role1 = new Role();
        $role1->setName('Sulu Administrator');
        $role1->setSystem('Sulu');
        $role1->setCreated($datetime);
        $role1->setChanged($datetime);
        self::$em->persist($role1);

        $role2 = new Role();
        $role2->setName('Sulu Manager');
        $role2->setSystem('Sulu');
        $role2->setCreated($datetime);
        $role2->setChanged($datetime);
        self::$em->persist($role2);

        $group1 = new Group();
        $group1->setName('Group1');
        $group1->setCreated($datetime);
        $group1->setChanged($datetime);
        $group1->addRole($role1);
        $group1->addRole($role2);
        self::$em->persist($group1);

        $group2 = new Group();
        $group2->setName('Group2');
        $group2->setCreated($datetime);
        $group2->setChanged($datetime);
        $group2->addRole($role1);
        self::$em->persist($group2);

        self::$em->flush();
    }

    public function tearDown()
    {
        parent::tearDown();
        self::$tool->dropSchema(self::$entities);
    }

    public function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\Permission'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\UserRole'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\Role'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\Group'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\UserGroup'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\SecurityType'),
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
    }

    public function testList()
    {
        $client = static::createClient();

        $client->request('GET', '/api/groups?flat=true');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertEquals('Group1', $response->_embedded->groups[0]->name);
        $this->assertEquals('Group2', $response->_embedded->groups[1]->name);
    }

    public function testGetById()
    {
        $client = static::createClient();

        $client->request('GET', '/api/groups/1');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Group1', $response->name);
        $this->assertCount(2, $response->roles);
        $this->assertEquals('Sulu Administrator', $response->roles[0]->name);
        $this->assertEquals('Sulu Manager', $response->roles[1]->name);
    }

    public function testPost()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/groups',
            array(
                'name' => 'Group3',
                'parent' => array('id' => 1),
                'roles' => array(
                    array(
                        'id' => 1
                    ),
                    array(
                        'id' => 2
                    )
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Group3', $response->name);
        $this->assertEquals(1, $response->parent->id);
        $this->assertCount(2, $response->roles);
        $this->assertEquals('Sulu Administrator', $response->roles[0]->name);
        $this->assertEquals('Sulu Manager', $response->roles[1]->name);

        $client->request(
            'GET',
            '/api/groups/3'
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
        $client = static::createClient();

        $client->request(
            'PUT',
            '/api/groups/1',
            array(
                'name' => 'Updated Group1',
                'parent' => array('id' => 2),
                'roles' => array(
                    array(
                        'id' => 1
                    )
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Updated Group1', $response->name);
        $this->assertEquals(2, $response->parent->id);
        $this->assertCount(1, $response->roles);
        $this->assertEquals('Sulu Administrator', $response->roles[0]->name);

        $client->request('GET', '/api/groups/1');

        $this->assertEquals('Updated Group1', $response->name);
        $this->assertEquals(2, $response->parent->id);
        $this->assertCount(1, $response->roles);
        $this->assertEquals('Sulu Administrator', $response->roles[0]->name);
    }

    public function testDelete()
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/groups'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(2, count($response->_embedded->groups));


        $client->request(
            'DELETE',
            '/api/groups/1'
        );

        $this->assertEquals(204, $client->getResponse()->getStatusCode());


        $client->request(
            'GET',
            '/api/groups'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(1, count($response->_embedded->groups));
    }
} 
