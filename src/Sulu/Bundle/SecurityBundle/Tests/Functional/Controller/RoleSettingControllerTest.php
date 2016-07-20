<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class RoleSettingControllerTest extends SuluTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Role
     */
    private $role;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->entityManager = $this->getEntityManager();
        $this->purgeDatabase();

        $role = new Role();
        $role->setName('Sulu Editor');
        $role->setSystem('Sulu');
        $this->entityManager->persist($role);
        $this->role = $role;

        $this->entityManager->flush();
    }

    public function testGetNotExisting()
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/roles/' . $this->role->getId() . '/settings/test');
        $this->assertHttpStatusCode(204, $client->getResponse());
        $this->assertEmpty($client->getResponse()->getContent());
    }

    public function testPut()
    {
        $key = 'test-key';
        $client = $this->createAuthenticatedClient();

        $client->request('PUT', '/api/roles/' . $this->role->getId() . '/settings/' . $key, ['value' => 'test-1']);
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals('test-1', json_decode($client->getResponse()->getContent()));

        return $key;
    }

    public function testGet()
    {
        $key = $this->testPut();

        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/roles/' . $this->role->getId() . '/settings/' . $key);
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals('test-1', json_decode($client->getResponse()->getContent()));
    }

    public function testPutArray()
    {
        $key = 'test-key';
        $client = $this->createAuthenticatedClient();

        $client->request(
            'PUT',
            '/api/roles/' . $this->role->getId() . '/settings/' . $key,
            ['value' => ['sulu' => 'awesome']]
        );
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals(['sulu' => 'awesome'], json_decode($client->getResponse()->getContent(), true));

        return $key;
    }

    public function testGetArray()
    {
        $key = $this->testPutArray();
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/api/roles/' . $this->role->getId() . '/settings/' . $key);
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals(['sulu' => 'awesome'], json_decode($client->getResponse()->getContent(), true));
    }
}
