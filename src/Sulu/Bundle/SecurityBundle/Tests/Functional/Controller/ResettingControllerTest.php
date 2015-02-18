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

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Bundle\SecurityBundle\Entity\User;

class ResettingControllerTest extends SuluTestCase
{

    private $user1;
    private $user2;
    private $user3;

    public function setUp()
    {
        $this->em = $this->db('ORM')->getOm();
        $this->purgeDatabase();

        // User 1
        $user1 = new User();
        $user1->setUsername('user1');
        $user1->setEmail('user1@test.com');
        $user1->setPassword('securepassword');
        $user1->setSalt('salt');
        $user1->setLocale('en');
        $this->em->persist($user1);
        $this->user1 = $user1;

        // User 2
        $user2 = new User();
        $user2->setUsername('user2');
        $user2->setEmail('user2@test.com');
        $user2->setPassword('securepassword');
        $user2->setSalt('salt');
        $user2->setLocale('en');
        $this->em->persist($user2);
        $this->user2 = $user2;

        // User 3
        $user3 = new User();
        $user3->setUsername('user3');
        $user3->setEmail('user3@test.com');
        $user3->setPassword('securepassword');
        $user3->setSalt('salt');
        $user3->setLocale('en');
        $this->em->persist($user3);
        $this->user3 = $user3;

        $this->em->flush();
    }

    public function testSendEmailAction() {
        $client = static::createClient(); // unauthenticated client
        //TODO: tests
        $this->assertEquals(1, 1);
    }

    public function testResetAction() {
        $this->assertEquals(1, 1);
    }
}
