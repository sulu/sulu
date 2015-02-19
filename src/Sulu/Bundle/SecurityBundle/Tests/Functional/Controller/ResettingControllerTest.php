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
    /** @var  User $user1 */
    private $user1;
    /** @var  User $user2 */
    private $user2;
    /** @var  User $user3 */
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

        $client->request('GET', '/security/reset/email', array(
            'user' => $this->user1->getEmail()
        ));

        $response = json_decode($client->getResponse()->getContent());

        // test response
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        /*$this->assertTrue($response['success']);
        $this->assertEquals($this->user1->getEmail(), $response['email']);
        // test user properties
        $this->assertTrue(is_string($this->user1->getPasswordResetToken()));
        $this->assertGreaterThan(new \DateTime(), $this->user1->getTokenExpiresAt());*/
    }

    public function testSendEmailActionWtihUsername() {
        $client = static::createClient(); // unauthenticated client
        //TODO: tests
        $this->assertEquals(1, 1);
    }

    public function testSendEmailActionWithUserWithoutEmail() {
        $client = static::createClient(); // unauthenticated client
        //TODO: tests
        $this->assertEquals(1, 1);
    }

    public function testSendEmailActionWithNoNewKey() {
        $client = static::createClient(); // unauthenticated client
        //TODO: tests
        $this->assertEquals(1, 1);
    }

    public function testSendEmailActionWithMissingUser() {
        $client = static::createClient(); // unauthenticated client
        //TODO: tests
        $this->assertEquals(1, 1);
    }

    public function testSendEmailActionWithNotExistingUser() {
        $client = static::createClient(); // unauthenticated client
        //TODO: tests
        $this->assertEquals(1, 1);
    }

    public function testSendEmailActionMultipleTimes() {
        $client = static::createClient(); // unauthenticated client
        //TODO: tests
        $this->assertEquals(1, 1);
    }

    public function testResetAction() {
        $this->assertEquals(1, 1);
    }

    public function testResetActionWithoutToken() {
        $this->assertEquals(1, 1);
    }

    public function testResetActionWithInvalidToken() {
        $this->assertEquals(1, 1);
    }

    public function testResetActionMultipleTimes() {
        $this->assertEquals(1, 1);
    }
}
