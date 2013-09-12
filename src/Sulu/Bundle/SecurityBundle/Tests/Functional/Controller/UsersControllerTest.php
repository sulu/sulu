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
use Sulu\Bundle\SecurityBundle\Entity\UserRole;

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
        $role1->setSystem('Sulu');
        $role1->setChanged(new DateTime());
        $role1->setCreated(new DateTime());
        self::$em->persist($role1);

        $role2 = new Role();
        $role2->setName('Role2');
        $role2->setSystem('Sulu');
        $role2->setChanged(new DateTime());
        $role2->setCreated(new DateTime());
        self::$em->persist($role2);

        $user = new User();
        $user->setUsername('admin');
        $user->setPassword('securepassword');
        $user->setSalt('salt');
        $user->setLocale('de');
        self::$em->persist($user);

        self::$em->flush();

        $userRole1 = new UserRole();
        $userRole1->setRole($role1);
        $userRole1->setUser($user);
        $userRole1->setLocale('de');
        self::$em->persist($userRole1);

        $userRole2 = new UserRole();
        $userRole2->setRole($role2);
        $userRole2->setUser($user);
        $userRole2->setLocale('de');
        self::$em->persist($userRole2);

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
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\User'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\UserRole'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\Role'),
            self::$em->getClassMetadata('Sulu\Bundle\SecurityBundle\Entity\Permission'),
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
        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
    }

    public function testGetByNotExistingId()
    {
        $client = static::createClient();

        $client->request('GET', '/security/api/users/10');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('10', $response->message);
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
                'salt' => 'salt',
                'userRoles' => array(
                    array(
                        'role' => array(
                            'id' => 1
                        ),
                        'locales' => array('de', 'en')
                    ),
                    array(
                        'role' => array(
                            'id' => 2
                        ),
                        'locales' => array('en')
                    ),
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals('verysecurepassword', $response->password);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('salt', $response->salt);
        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('de', $response->userRoles[0]->locales[0]);
        $this->assertEquals('en', $response->userRoles[0]->locales[1]);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
        $this->assertEquals('en', $response->userRoles[1]->locales[0]);

        $client->request(
            'GET',
            '/security/api/users/2'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals('verysecurepassword', $response->password);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('salt', $response->salt);
        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('de', $response->userRoles[0]->locales[0]);
        $this->assertEquals('en', $response->userRoles[0]->locales[1]);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
        $this->assertEquals('en', $response->userRoles[1]->locales[0]);
    }

    public function testPostWithMissingArgument() {
        $client = static::createClient();

        $client->request(
            'POST',
            '/security/api/users',
            array(
                'password' => 'verysecurepassword',
                'locale' => 'en',
                'userRoles' => array(
                    array(
                        'role' => array(
                            'id' => 1
                        ),
                        'locales' => '["de"]'
                    ),
                    array(
                        'role' => array(
                            'id' => 2
                        ),
                        'locales' => '["de"]'
                    ),
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertContains('username', $response->message);
    }

    public function testDelete()
    {
        $client = static::createClient();

        $client->request('DELETE', '/security/api/users/1');

        $this->assertEquals(204, $client->getResponse()->getStatusCode());

        $client->request('GET', '/security/api/users/1');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testDeleteNotExisting()
    {
        $client = static::createClient();

        $client->request('DELETE', '/security/api/users/15');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
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
                'salt' => 'newsalt',
                'userRoles' => array(
                    array(
                        'id' => 1,
                        'role' => array(
                            'id' => 1
                        ),
                        'locales' => array('de', 'en')
                    ),
                    array(
                        'id' => 2,
                        'role' => array(
                            'id' => 2
                        ),
                        'locales' => array('en')
                    ),
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals('verysecurepassword', $response->password);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('newsalt', $response->salt);
        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('de', $response->userRoles[0]->locales[0]);
        $this->assertEquals('en', $response->userRoles[0]->locales[1]);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
        $this->assertEquals('en', $response->userRoles[1]->locales[0]);

        $client->request(
            'GET',
            '/security/api/users/1'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('manager', $response->username);
        $this->assertEquals('verysecurepassword', $response->password);
        $this->assertEquals('en', $response->locale);
        $this->assertEquals('newsalt', $response->salt);
        $this->assertEquals('Role1', $response->userRoles[0]->role->name);
        $this->assertEquals('de', $response->userRoles[0]->locales[0]);
        $this->assertEquals('en', $response->userRoles[0]->locales[1]);
        $this->assertEquals('Role2', $response->userRoles[1]->role->name);
        $this->assertEquals('en', $response->userRoles[1]->locales[0]);
    }

    public function testPutWithMissingArgument()
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
                        'role' => array(
                            'id' => 1
                        ),
                        'locales' => array('de', 'en')
                    ),
                    array(
                        'role' => array(
                            'id' => 2
                        ),
                        'locales' => array('en')
                    ),
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertContains('salt', $response->message);
    }
}