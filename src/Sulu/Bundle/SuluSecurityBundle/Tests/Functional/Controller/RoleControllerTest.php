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

use Sulu\Bundle\SecurityBundle\Entity\SecurityType;
use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;

class RolesControllerTest extends DatabaseTestCase
{
    /**
     * @var array
     */
    protected static $entities;

    /**
     * @var SecurityType
     */
    protected static $securityType1;

    /**
     * @var SecurityType
     */
    protected static $securityType2;

    /**
     * @var SchemaTool
     */
    protected static $tool;

    public function setUp()
    {
        $this->setUpSchema();

        self::$securityType1 = new SecurityType();
        self::$securityType1->setName('Security Type 1');
        self::$em->persist(self::$securityType1);

        self::$securityType2 = new SecurityType();
        self::$securityType2->setName('Security Type 2');
        self::$em->persist(self::$securityType2);

        $role = new Role();
        $role->setName('Sulu Administrator');
        $role->setSystem('Sulu');
        $role->setCreated(new DateTime());
        $role->setChanged(new DateTime());
        $role->setSecurityType(self::$securityType1);
        self::$em->persist($role);

        $role2 = new Role();
        $role2->setName('Sulu Editor');
        $role2->setSystem('Sulu');
        $role2->setCreated(new DateTime());
        $role2->setChanged(new DateTime());
        self::$em->persist($role2);

        self::$em->flush();

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

        $permission1 = new Permission();
        $permission1->setRole($role2);
        $permission1->setContext('context1');
        $permission1->setPermissions(64);
        self::$em->persist($permission1);

        $permission2 = new Permission();
        $permission2->setRole($role2);
        $permission2->setContext('context2');
        $permission2->setPermissions(35);
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

        $client->request('GET', '/api/roles?flat=true');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(2, count($response->_embedded->roles));
        $this->assertEquals('Sulu Administrator', $response->_embedded->roles[0]->name);
        $this->assertEquals('Sulu', $response->_embedded->roles[0]->system);
    }

    public function testGetById()
    {
        $client = static::createClient();

        $client->request('GET', '/api/roles/1');
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
        $this->assertEquals('Security Type 1', $response->securityType->name);
    }

    public function testPost()
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/roles',
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
                ),
                'securityType' => array(
                    'id' => 2
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
        $this->assertEquals('Security Type 2', $response->securityType->name);

        $client->request(
            'GET',
            '/api/roles/3'
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
        $this->assertEquals('Security Type 2', $response->securityType->name);
    }

    public function testPut()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/api/roles/1',
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
                ),
                'securityType' => array(
                    'id' => 2
                )
            )
        );

        $response = json_decode($client->getResponse()->getContent());

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
        $this->assertEquals('Security Type 2', $response->securityType->name);

        $client->request(
            'GET',
            '/api/roles/1'
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
        $this->assertEquals('Security Type 2', $response->securityType->name);
    }

    public function testPutRemoveSecurityType()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/api/roles/1',
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
        $this->assertObjectNotHasAttribute('securityType', $response);

        $client->request(
            'GET',
            '/api/roles/1'
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
        $this->assertObjectNotHasAttribute('securityType', $response);
    }

    public function testPutNotExisting()
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/api/roles/10',
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
            'GET',
            '/api/roles'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(2, count($response->_embedded->roles));


        $client->request(
            'DELETE',
            '/api/roles/1'
        );

        $this->assertEquals(204, $client->getResponse()->getStatusCode());


        $client->request(
            'GET',
            '/api/roles'
        );

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(1, count($response->_embedded->roles));
    }

    public function testDeleteNotExisting()
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
            '/api/roles/10'
        );

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertContains('10', $response->message);
    }

    public function testGetAllRoles(){
        $client = static::createClient();

        $client->request('GET', '/api/roles');

        $response = json_decode($client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(2, count($response->_embedded->roles));

        $this->assertEquals('Sulu Administrator', $response->_embedded->roles[0]->name);
        $this->assertEquals('Sulu Editor', $response->_embedded->roles[1]->name);

        $this->assertEquals('Sulu', $response->_embedded->roles[0]->system);
        $this->assertEquals('Sulu', $response->_embedded->roles[1]->system);

        $this->assertEquals('context1', $response->_embedded->roles[0]->permissions[0]->context);
        $this->assertEquals('context2', $response->_embedded->roles[1]->permissions[1]->context);
    }
}
