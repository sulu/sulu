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

use DateTime;
use Doctrine\ORM\Tools\SchemaTool;

use Sulu\Bundle\CoreBundle\Tests\DatabaseTestCase;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;

class UsersControllerTest extends DatabaseTestCase
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

        $role1 = new Role();
        $role1->setName('Role1');
        $role1->setChanged(new DateTime());
        $role1->setCreated(new DateTime());
        self::$em->persist($role1);

        $role2 = new Role();
        $role2->setName('Role1');
        $role2->setChanged(new DateTime());
        $role2->setCreated(new DateTime());
        self::$em->persist($role2);

        $user = new User();
        $user->setUsername('admin');
        $user->setPassword('securepassword');
        $user->setLocale('de');
        self::$em->persist($user);

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
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\User'),
        );

        self::$tool->dropSchema(self::$entities);
        self::$tool->createSchema(self::$entities);
    }

    public function testList()
    {
        $client = static::createClient();

        $client->request('GET', '/security/api/users/list');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(1, $response->total);
        $this->assertEquals(1, count($response->total));
        $this->assertEquals('admin', $response->items[0]->username);
        $this->assertEquals('securepassword', $response->items[0]->password);
        $this->assertEquals('de', $response->items[0]->locale);
    }

    public function testGetById()
    {
        $client = static::createClient();

        $client->request('GET', '/security/api/users/1');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('admin', $response->username);
        $this->assertEquals('securepassword', $response->password);
        $this->assertEquals('de', $response->locale);
    }

    public function testPost()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/security/api/users',
            array(
                'username' => 'manager',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'userRoles' => array(
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

        $this->assertEquals('manager', $response->username);
        $this->assertEquals('verysecurepassword', $response->password);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('Role1', $response->userRoles[0]->name);
        $this->assertEquals('Role2', $response->userRoles[1]->name);

        $client->request(
            'GET',
            '/security/api/users/2'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals('verysecurepassword', $response->password);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('Role1', $response->userRoles[0]->name);
        $this->assertEquals('Role2', $response->userRoles[1]->name);
    }

    public function testPut()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/security/api/users/1',
            array(
                'username' => 'manager',
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'userRoles' => array(
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

        $this->assertEquals('manager', $response->username);
        $this->assertEquals('verysecurepassword', $response->password);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('Role1', $response->userRoles[0]->name);
        $this->assertEquals('Role2', $response->userRoles[1]->name);

        $client->request(
            'GET',
            '/security/api/users/1'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals('verysecurepassword', $response->password);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('Role1', $response->userRoles[0]->name);
        $this->assertEquals('Role2', $response->userRoles[1]->name);
    }

    public function testDelete()
    {
        $client = static::createClient();

        $client->request('DELETE', '/security/api/users/1');

        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        $client->request('GET', '/security/api/users/1');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
}