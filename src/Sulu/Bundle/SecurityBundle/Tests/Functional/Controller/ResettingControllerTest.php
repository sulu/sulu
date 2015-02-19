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
        $user2->setEmail(null);
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
        $user3->setPasswordResetToken('thisisasupersecrettoken');
        $user3->setTokenExpiresAt((new \DateTime())->add(new \DateInterval('PT24H')));
        $this->em->persist($user3);
        $this->user3 = $user3;

        $this->em->flush();
    }

    public function testSendEmailAction() {
        //$client = static::createClient(); // unauthenticated client
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        $client->request('GET', '/security/reset/email', array(
            'user' => $this->user1->getEmail()
        ));

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        // asserting response
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($response->success);
        $this->assertEquals($this->user1->getEmail(), $response->email);

        // asserting user properties
        $this->em->refresh($this->user1);
        $this->assertTrue(is_string($this->user1->getPasswordResetToken()));
        $this->assertGreaterThan(new \DateTime(), $this->user1->getTokenExpiresAt());

        // asserting sent mail
        $this->assertEquals(1, $mailCollector->getMessageCount());
        $message = $mailCollector->getMessages()[0];
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals($this->user1->getEmail(), key($message->getTo()));
        $this->assertContains($this->user1->getPasswordResetToken(), $message->getBody());
    }

    public function testSendEmailActionWtihUsername() {
        //$client = static::createClient(); // unauthenticated client
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        $client->request('GET', '/security/reset/email', array(
            'user' => $this->user1->getUsername()
        ));

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        // asserting response
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($response->success);
        $this->assertEquals($this->user1->getEmail(), $response->email);

        // asserting user properties
        $this->em->refresh($this->user1);
        $this->assertTrue(is_string($this->user1->getPasswordResetToken()));
        $this->assertGreaterThan(new \DateTime(), $this->user1->getTokenExpiresAt());

        // asserting sent mail
        $this->assertEquals(1, $mailCollector->getMessageCount());
        $message = $mailCollector->getMessages()[0];
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals($this->user1->getEmail(), key($message->getTo()));
        $this->assertContains($this->user1->getPasswordResetToken(), $message->getBody());
    }

    public function testSendEmailActionWithUserWithoutEmail() {
        //$client = static::createClient(); // unauthenticated client
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        $client->request('GET', '/security/reset/email', array(
            'user' => $this->user2->getUsername()
        ));

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        // asserting response
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($response->success);
        $this->assertEquals('installation.email@sulu.test', $response->email);

        // asserting user properties
        $this->em->refresh($this->user2);
        $this->assertTrue(is_string($this->user2->getPasswordResetToken()));
        $this->assertGreaterThan(new \DateTime(), $this->user2->getTokenExpiresAt());

        // asserting sent mail
        $this->assertEquals(1, $mailCollector->getMessageCount());
        $message = $mailCollector->getMessages()[0];
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals('installation.email@sulu.test', key($message->getTo()));
        $this->assertContains($this->user2->getPasswordResetToken(), $message->getBody());
    }

    public function testResendEmailAction() {
        //$client = static::createClient(); // unauthenticated client
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        $client->request('GET', '/security/reset/email/resend', array(
            'user' => $this->user3->getEmail()
        ));

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        // asserting response
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($response->success);
        $this->assertEquals($this->user3->getEmail(), $response->email);

        // asserting user properties
        $this->em->refresh($this->user3);
        $this->assertEquals('thisisasupersecrettoken', $this->user3->getPasswordResetToken());
        $this->assertGreaterThan(new \DateTime(), $this->user3->getTokenExpiresAt());

        // asserting sent mail
        $this->assertEquals(1, $mailCollector->getMessageCount());
        $message = $mailCollector->getMessages()[0];
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals($this->user3->getEmail(), key($message->getTo()));
        $this->assertContains($this->user3->getPasswordResetToken(), $message->getBody());
    }

    public function testSendEmailActionWithMissingUser() {
        //$client = static::createClient(); // unauthenticated client
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        $client->request('GET', '/security/reset/email');

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertEquals(0, $response->code);
        $this->assertEquals(0, $mailCollector->getMessageCount());
    }

    public function testSendEmailActionWithNotExistingUser() {
        //$client = static::createClient(); // unauthenticated client
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        $client->request('GET', '/security/reset/email', array(
            'user' => 'lord.voldemort@askab.an'
        ));

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertEquals(0, $response->code);
        $this->assertEquals(0, $mailCollector->getMessageCount());
    }

    public function testSendEmailActionMultipleTimes() {
        //$client = static::createClient(); // unauthenticated client
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        $client->request('GET', '/security/reset/email', array(
            'user' => $this->user1->getUsername()
        ));
        $response = json_decode($client->getResponse()->getContent());
        // asserting response
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($response->success);
        $this->assertEquals($this->user1->getEmail(), $response->email);

        // second request should be blocked
        $client->request('GET', '/security/reset/email', array(
            'user' => $this->user1->getUsername()
        ));
        $response = json_decode($client->getResponse()->getContent());
        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        // asserting response
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertEquals(1003, $response->code);
        $this->assertEquals(0, $mailCollector->getMessageCount());
    }

    public function testResetAction() {
        //$client = static::createClient(); // unauthenticated client
        $client = $this->createAuthenticatedClient();
        $newPassword = 'anewpasswordishouldremeber';

        $client->request('GET', '/security/reset', array(
            'token' => 'thisisasupersecrettoken',
            'password' => $newPassword
        ));
        $response = json_decode($client->getResponse()->getContent());
        $this->em->refresh($this->user3);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($response->success);

        $encoder = $this->container->get('security.encoder_factory')->getEncoder($this->user3);
        $this->assertEquals($encoder->encodePassword($newPassword, $this->user3->getSalt()), $this->user3->getPassword());
        $this->assertNull($this->user3->getPasswordResetToken());
        $this->assertNull($this->user3->getTokenExpiresAt());
    }

    public function testResetActionWithoutToken() {
        //$client = static::createClient(); // unauthenticated client
        $client = $this->createAuthenticatedClient();
        $passwordBefore = $this->user3->getPassword();

        $client->request('GET', '/security/reset', array(
            'password' => 'thispasswordshouldnotbeapplied'
        ));
        $response = json_decode($client->getResponse()->getContent());
        $this->em->refresh($this->user3);

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertEquals(1005, $response->code);
        $this->assertEquals($passwordBefore, $this->user3->getPassword());
    }

    public function testResetActionWithInvalidToken() {
        //$client = static::createClient(); // unauthenticated client
        $client = $this->createAuthenticatedClient();
        $passwordBefore = $this->user3->getPassword();

        $client->request('GET', '/security/reset', array(
            'token' => 'thistokendoesnotexist',
            'password' => 'thispasswordshouldnotbeapplied'
        ));
        $response = json_decode($client->getResponse()->getContent());
        $this->em->refresh($this->user3);

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $this->assertEquals(1005, $response->code);
        $this->assertEquals($passwordBefore, $this->user3->getPassword());
    }
}
