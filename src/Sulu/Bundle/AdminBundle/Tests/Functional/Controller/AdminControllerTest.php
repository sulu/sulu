<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Controller;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\SecurityBundle\Entity\UserSetting;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class AdminControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function setUp()
    {
        $this->purgeDatabase();
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
    }

    public function testIndexAction()
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/admin/'); // required because test user does not exist otherwise

        $userSetting = new UserSetting();
        $userSetting->setKey('test');
        $userSetting->setValue(json_encode(['key' => 'value']));
        $userSetting->setUser($this->getTestUser());
        $this->em->persist($userSetting);
        $this->em->flush();

        $client->request('GET', '/admin/');

        $this->assertContains('"settings":{"test":{"key":"value"}}', $client->getResponse()->getContent());
    }
}
