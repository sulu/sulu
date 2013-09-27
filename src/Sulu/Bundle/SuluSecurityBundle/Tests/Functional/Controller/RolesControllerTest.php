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
use Sulu\Bundle\SecurityBundle\Entity\Permission;
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
        $role->setCreated(new DateTime());
        $role->setChanged(new DateTime());
        self::$em->persist($role);

        $permission1 = new Permission();
        $permission1->setRole($role);
        $permission1->setContext('context1');
        $permission1->setPermissions(15);
        self::$em->persist($permission1);

        $permission2 = new Permission();
        $permission2->setRole($role);
        $permission2->setContext('context2');
        $permission2->setPermissions(17);
        self::$em->persist($permission2);

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
    }

    public function testGetById()
    {
        $client = static::createClient();

        $client->request('GET', '/security/api/roles/1');
        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Sulu Administrator', $response->name);
        $this->assertEquals('Sulu', $response->system);
        $this->assertEquals(2, count($response->permissions));
        $this->assertEquals('context1', $response->permissions[0]->context);
        $this->assertEquals(false, $response->permissions[0]->permissions->view);
        $this->assertEquals(false, $response->permissions[0]->permissions->add);
        $this->assertEquals(false, $response->permissions[0]->permissions->edit);
        $this->assertEquals(true, $response->permissions[0]->permissions->delete);
        $this->assertEquals(true, $response->permissions[0]->permissions->archive);
        $this->assertEquals(true, $response->permissions[0]->permissions->live);
        $this->assertEquals(true, $response->permissions[0]->permissions->security);
        $this->assertEquals('context2', $response->permissions[1]->context);
        $this->assertEquals(false, $response->permissions[1]->permissions->view);
        $this->assertEquals(false, $response->permissions[1]->permissions->add);
        $this->assertEquals(true, $response->permissions[1]->permissions->edit);
        $this->assertEquals(false, $response->permissions[1]->permissions->delete);
        $this->assertEquals(false, $response->permissions[1]->permissions->archive);
        $this->assertEquals(false, $response->permissions[1]->permissions->live);
        $this->assertEquals(true, $response->permissions[1]->permissions->security);
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
                'permissions' => array(
                    array(
                        'context' => 'portal1',
                        'permissions' => array(
                            'view' => false,
                            'add' => false,
                            'edit' => true,
                            'delete' => true,
                            'archive' => false,
                            'live' => true,
                            'security' => false
                        ),
                    ),
                    array(
                        'context' => 'portal2',
                        'permissions' => array(
                            'view' => false,
                            'add' => false,
                            'edit' => false,
                            'delete' => true,
                            'archive' => false,
                            'live' => false,
                            'security' => false
                        )
                    )
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('Sulu', $response->system);
        $this->assertEquals(2, count($response->permissions));
        $this->assertEquals('portal1', $response->permissions[0]->context);
        $this->assertEquals(false, $response->permissions[0]->permissions->view);
        $this->assertEquals(false, $response->permissions[0]->permissions->add);
        $this->assertEquals(true, $response->permissions[0]->permissions->edit);
        $this->assertEquals(true, $response->permissions[0]->permissions->delete);
        $this->assertEquals(false, $response->permissions[0]->permissions->archive);
        $this->assertEquals(true, $response->permissions[0]->permissions->live);
        $this->assertEquals(false, $response->permissions[0]->permissions->security);
        $this->assertEquals('portal2', $response->permissions[1]->context);
        $this->assertEquals(false, $response->permissions[1]->permissions->view);
        $this->assertEquals(false, $response->permissions[1]->permissions->add);
        $this->assertEquals(false, $response->permissions[1]->permissions->edit);
        $this->assertEquals(true, $response->permissions[1]->permissions->delete);
        $this->assertEquals(false, $response->permissions[1]->permissions->archive);
        $this->assertEquals(false, $response->permissions[1]->permissions->live);
        $this->assertEquals(false, $response->permissions[1]->permissions->security);

        $client->request(
            'GET',
            '/security/api/roles/2'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('Sulu', $response->system);
        $this->assertEquals(2, count($response->permissions));
        $this->assertEquals('portal1', $response->permissions[0]->context);
        $this->assertEquals(false, $response->permissions[0]->permissions->view);
        $this->assertEquals(false, $response->permissions[0]->permissions->add);
        $this->assertEquals(true, $response->permissions[0]->permissions->edit);
        $this->assertEquals(true, $response->permissions[0]->permissions->delete);
        $this->assertEquals(false, $response->permissions[0]->permissions->archive);
        $this->assertEquals(true, $response->permissions[0]->permissions->live);
        $this->assertEquals(false, $response->permissions[0]->permissions->security);
        $this->assertEquals('portal2', $response->permissions[1]->context);
        $this->assertEquals(false, $response->permissions[1]->permissions->view);
        $this->assertEquals(false, $response->permissions[1]->permissions->add);
        $this->assertEquals(false, $response->permissions[1]->permissions->edit);
        $this->assertEquals(true, $response->permissions[1]->permissions->delete);
        $this->assertEquals(false, $response->permissions[1]->permissions->archive);
        $this->assertEquals(false, $response->permissions[1]->permissions->live);
        $this->assertEquals(false, $response->permissions[1]->permissions->security);
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
                'permissions' => array(
                    array(
                        'id' => 1,
                        'context' => 'portal1',
                        'permissions' => array(
                            'view' => true,
                            'add' => true,
                            'edit' => true,
                            'delete' => true,
                            'archive' => false,
                            'live' => false,
                            'security' => false
                        ),
                    ),
                    array(
                        'id' => 2,
                        'context' => 'portal2',
                        'permissions' => array(
                            'view' => false,
                            'add' => false,
                            'edit' => false,
                            'delete' => false,
                            'archive' => true,
                            'live' => true,
                            'security' => true
                        )
                    ),
                    array(
                        'context' => 'portal3',
                        'permissions' => array(
                            'view' => false,
                            'add' => false,
                            'edit' => false,
                            'delete' => false,
                            'archive' => true,
                            'live' => true,
                            'security' => true
                        )
                    )
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());
        var_dump($response);

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('Sulu', $response->system);
        $this->assertEquals('portal1', $response->permissions[0]->context);
        $this->assertEquals(true, $response->permissions[0]->permissions->view);
        $this->assertEquals(true, $response->permissions[0]->permissions->add);
        $this->assertEquals(true, $response->permissions[0]->permissions->edit);
        $this->assertEquals(true, $response->permissions[0]->permissions->delete);
        $this->assertEquals(false, $response->permissions[0]->permissions->archive);
        $this->assertEquals(false, $response->permissions[0]->permissions->live);
        $this->assertEquals(false, $response->permissions[0]->permissions->security);
        $this->assertEquals('portal2', $response->permissions[1]->context);
        $this->assertEquals(false, $response->permissions[1]->permissions->view);
        $this->assertEquals(false, $response->permissions[1]->permissions->add);
        $this->assertEquals(false, $response->permissions[1]->permissions->edit);
        $this->assertEquals(false, $response->permissions[1]->permissions->delete);
        $this->assertEquals(true, $response->permissions[1]->permissions->archive);
        $this->assertEquals(true, $response->permissions[1]->permissions->live);
        $this->assertEquals(true, $response->permissions[1]->permissions->security);
        $this->assertEquals('portal3', $response->permissions[2]->context);
        $this->assertEquals(false, $response->permissions[2]->permissions->view);
        $this->assertEquals(false, $response->permissions[2]->permissions->add);
        $this->assertEquals(false, $response->permissions[2]->permissions->edit);
        $this->assertEquals(false, $response->permissions[2]->permissions->delete);
        $this->assertEquals(true, $response->permissions[2]->permissions->archive);
        $this->assertEquals(true, $response->permissions[2]->permissions->live);
        $this->assertEquals(true, $response->permissions[2]->permissions->security);

        $client->request(
            'GET',
            '/security/api/roles/1'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals('Portal Manager', $response->name);
        $this->assertEquals('Sulu', $response->system);
        $this->assertEquals(true, $response->permissions[0]->permissions->view);
        $this->assertEquals(true, $response->permissions[0]->permissions->add);
        $this->assertEquals(true, $response->permissions[0]->permissions->edit);
        $this->assertEquals(true, $response->permissions[0]->permissions->delete);
        $this->assertEquals(false, $response->permissions[0]->permissions->archive);
        $this->assertEquals(false, $response->permissions[0]->permissions->live);
        $this->assertEquals(false, $response->permissions[0]->permissions->security);
        $this->assertEquals('portal2', $response->permissions[1]->context);
        $this->assertEquals(false, $response->permissions[1]->permissions->view);
        $this->assertEquals(false, $response->permissions[1]->permissions->add);
        $this->assertEquals(false, $response->permissions[1]->permissions->edit);
        $this->assertEquals(false, $response->permissions[1]->permissions->delete);
        $this->assertEquals(true, $response->permissions[1]->permissions->archive);
        $this->assertEquals(true, $response->permissions[1]->permissions->live);
        $this->assertEquals(true, $response->permissions[1]->permissions->security);
        $this->assertEquals('portal3', $response->permissions[2]->context);
        $this->assertEquals(false, $response->permissions[2]->permissions->view);
        $this->assertEquals(false, $response->permissions[2]->permissions->add);
        $this->assertEquals(false, $response->permissions[2]->permissions->edit);
        $this->assertEquals(false, $response->permissions[2]->permissions->delete);
        $this->assertEquals(true, $response->permissions[2]->permissions->archive);
        $this->assertEquals(true, $response->permissions[2]->permissions->live);
        $this->assertEquals(true, $response->permissions[2]->permissions->security);
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