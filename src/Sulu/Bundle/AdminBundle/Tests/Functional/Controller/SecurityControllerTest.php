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

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\MediaBundle\Entity\CollectionType;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class SecurityControllerTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    public function setUp()
    {
        $this->purgeDatabase();
        $this->em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        $collectionType1 = new CollectionType();
        $collectionType1->setId(1);
        $collectionType1->setName('default');
        $collectionType1->setKey('default');
        $this->em->persist($collectionType1);

        $collectionType2 = new CollectionType();
        $collectionType2->setId(1);
        $collectionType2->setName('system');
        $collectionType2->setKey('system');
        $this->em->persist($collectionType2);

        $this->em->flush();
    }

    public function testLoginAction()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/admin/login');

        $this->assertGreaterThan(0, $crawler->filter('#main.login')->count());
    }

    public function testResetAction()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/admin/reset/test-token');

        $this->assertGreaterThan(0, $crawler->filter('#main.login')->count());
        $this->assertGreaterThan(0, $crawler->filter('div[data-aura-reset-token="test-token"]')->count());
    }
}
