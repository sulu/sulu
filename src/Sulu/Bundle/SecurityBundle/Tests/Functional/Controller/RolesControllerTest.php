<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Doctrine\ORM\Tools\SchemaTool;

use Sulu\Bundle\CoreBundle\Tests\DatabaseTestCase;
use Sulu\Bundle\SecurityBundle\Entity\Role;

class RolesControllerTest extends DatabaseTestCase
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

        $role = new Role();
        $role->setName('Sulu Administrator');
        $role->setSystem('Sulu');
        $role->setContext('General');
        $role->setPermission(15);
        self::$em->persist($role);

        self::$em->flush();
    }

    public function tearDown()
    {
        parent::tearDown();
    }

    public function setUpSchema()
    {
        self::$tool = new SchemaTool(self::$em);

        self::$entities = array(
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\Role'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\ContactRole'),
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
    }

    public function testList()
    {
        $client = static::createClient();

        $client->request('GET', '/security/api/roles/list');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals(1, count($response->total));
        $this->assertEquals('Sulu Administrator', $response->items[0]->name);
        $this->assertEquals('Sulu', $response->items[0]->system);
        $this->assertEquals('General', $response->items[0]->context);
        $this->assertEquals(15, $response->items[0]->permission);
    }

    public function testGetById()
    {
        $client = static::createClient();

        $client->request('GET', '/security/api/roles/1');
        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals('Sulu Administrator', $response->name);
        $this->assertEquals('Sulu', $response->system);
        $this->assertEquals('General', $response->context);
        $this->assertEquals(15, $response->permission);
    }

    public function testPost()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/security/api/roles',
            array(
                'name' => 'Portal Manager',
                'system' => 'Sulu',
                'context' => 'Portal',
                'permission' => 17
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('Sulu', $response->system);
        $this->assertEquals('Portal', $response->context);
        $this->assertEquals(17, $response->permission);

        $client->request(
            'GET',
            '/security/api/roles/2'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('Sulu', $response->system);
        $this->assertEquals('Portal', $response->context);
        $this->assertEquals(17, $response->permission);
    }

    public function testPut()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/security/api/roles/1',
            array(
                'name' => 'Portal Manager',
                'system' => 'Sulu',
                'context' => 'Portal',
                'permission' => 17
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('Sulu', $response->system);
        $this->assertEquals('Portal', $response->context);
        $this->assertEquals(17, $response->permission);

        $client->request(
            'GET',
            '/security/api/roles/1'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('Sulu', $response->system);
        $this->assertEquals('Portal', $response->context);
        $this->assertEquals(17, $response->permission);
    }

    public function testPutNotExisting()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/security/api/roles/10',
            array(
                'name' => 'Portal Manager',
                'system' => 'Sulu',
                'context' => 'Portal',
                'permission' => 17
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('10', $response->message);
    }

    public function testDelete()
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
            '/security/api/roles/1'
        );

        $this->assertEquals(204, $client->getResponse()->getStatusCode());
    }

    public function testDeleteNotExisting()
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
            '/security/api/roles/10'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('10', $response->message);
    }
}