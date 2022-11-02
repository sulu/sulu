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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

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
     * @var KernelBrowser
     */
    private $client;

    public function setUp(): void
    {
        $this->client = $this->createAuthenticatedClient();
        $this->entityManager = $this->getEntityManager();
        $this->purgeDatabase();

        $role = new Role();
        $role->setName('Sulu Editor');
        $role->setSystem('Sulu');
        $this->entityManager->persist($role);
        $this->role = $role;

        $this->entityManager->flush();
        $this->entityManager->clear();
    }

    public function testGetNotExisting(): void
    {
        $this->client->jsonRequest('GET', '/api/roles/' . $this->role->getId() . '/settings/test');
        $this->assertHttpStatusCode(204, $this->client->getResponse());
        $this->assertEmpty($this->client->getResponse()->getContent());
    }

    public function testPut()
    {
        $key = 'test-key';

        $this->client->jsonRequest('PUT', '/api/roles/' . $this->role->getId() . '/settings/' . $key, ['value' => 'test-1']);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals('test-1', \json_decode($this->client->getResponse()->getContent()));

        return $key;
    }

    public function testGet(): void
    {
        $key = $this->testPut();

        $this->client->jsonRequest('GET', '/api/roles/' . $this->role->getId() . '/settings/' . $key);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals('test-1', \json_decode($this->client->getResponse()->getContent()));
    }

    public function testPutArray()
    {
        $key = 'test-key';

        $this->client->jsonRequest(
            'PUT',
            '/api/roles/' . $this->role->getId() . '/settings/' . $key,
            ['value' => ['sulu' => 'awesome']]
        );
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals(['sulu' => 'awesome'], \json_decode($this->client->getResponse()->getContent(), true));

        return $key;
    }

    public function testGetArray(): void
    {
        $key = $this->testPutArray();

        $this->client->jsonRequest('GET', '/api/roles/' . $this->role->getId() . '/settings/' . $key);
        $this->assertHttpStatusCode(200, $this->client->getResponse());
        $this->assertEquals(['sulu' => 'awesome'], \json_decode($this->client->getResponse()->getContent(), true));
    }
}
