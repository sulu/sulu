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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authentication\RoleInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;

class PermissionControllerTest extends SuluTestCase
{
    /**
     * @var RoleInterface
     */
    private $role;

    /**
     * @var MutableAclProviderInterface
     */
    private $aclProvider;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function setUp()
    {
        $this->em = $this->db('ORM')->getOm();
        $this->purgeDatabase();

        $this->aclProvider = $this->getContainer()->get('security.acl.provider');

        $this->role = new Role();
        $this->role->setName('Administrator');
        $this->role->setSystem('Sulu');
        $this->em->persist($this->role);
        $this->em->flush();
    }

    public function providePermissionData()
    {
        return array(
            array(
                '1',
                'Acme\Example',
                array(
                    'add' => 'true',
                    'view' => true,
                    'delete' => false,
                    'edit' => 'false',
                    'archive' => false,
                    'live' => false,
                    'security' => false,
                ),
            ),
        );
    }

    /**
     * @dataProvider providePermissionData
     */
    public function testGetAction($id, $class, $permissions)
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/permissions', array(
            'id' => $id,
            'type' => $class,
            'permissions' => array(
                'SULU_ROLE_ADMINISTRATOR' => $permissions,
            ),
        ));

        $client->request('GET', '/api/permissions?id=' . $id . '&type=' . $class);

        $response = json_decode($client->getResponse()->getContent(), true);

        array_walk($permissions, function (&$permissionLine) {
            $permissionLine = $permissionLine === 'true' || $permissionLine === true;
        });

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(
            array(
                'id' => $id,
                'type' => $class,
                'permissions' => array(
                    'SULU_ROLE_ADMINISTRATOR' => $permissions,
                ),
            ),
            $response
        );
    }

    /**
     * @dataProvider providePermissionData
     */
    public function testPostAction($id, $class, $permissions)
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/permissions', array(
            'id' => $id,
            'type' => $class,
            'permissions' => array(
                'SULU_ROLE_ADMINISTRATOR' => $permissions,
            ),
        ));

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertEquals(
            array(
                'id' => $id,
                'type' => $class,
                'permissions' => array(
                    'SULU_ROLE_ADMINISTRATOR' => $permissions,
                ),
            ),
            $response
        );

        $acl = $this->aclProvider->findAcl(new ObjectIdentity($id, $class));
        $sid = new RoleSecurityIdentity('SULU_ROLE_ADMINISTRATOR');

        array_walk($permissions, function (&$permissionLine) {
            $permissionLine = $permissionLine === 'true' || $permissionLine === true;
        });

        foreach ($acl->getObjectAces() as $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $this->assertEquals(
                    $this->getContainer()
                        ->get('sulu_security.mask_converter')
                        ->convertPermissionsToNumber($permissions),
                    $ace->getMask()
                );
            }
        }
    }

    public function provideWrongPermissionData()
    {
        return array(
            array(null, null, null),
            array('1', null, array()),
            array(null, 'Acme\Example', array()),
            array('1', 'Acme\Example', null),
        );
    }

    /**
     * @dataProvider provideWrongPermissionData
     */
    public function testPostActionWithWrongData($id, $class, $permissions)
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/api/permissions', array(
            'id' => $id,
            'type' => $class,
            'permissions' => $permissions,
        ));

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }
}
