<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Controller;

use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Controller\ResettingController;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ResettingControllerTest extends SuluTestCase
{
    /**
     * @var ObjectManager
     */
    private $em;

    /** @var User $user1 */
    private $user1;
    /** @var User $user2 */
    private $user2;
    /** @var User $user3 */
    private $user3;

    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();

        // User 1
        $user1 = new User();
        $user1->setUsername('user1');
        $user1->setEmail('user1@test.com');
        $user1->setPassword('securepassword');
        $user1->setSalt('salt');
        $user1->setLocale('en');
        $contact1 = new Contact();
        $contact1->setFirstName('User1');
        $contact1->setLastName('Test');
        $user1->setContact($contact1);
        $this->em->persist($contact1);
        $this->user1 = $user1;
        $this->em->persist($this->user1);

        // User 2
        $user2 = new User();
        $user2->setUsername('user2');
        $user2->setEmail(null);
        $user2->setPassword('securepassword');
        $user2->setSalt('salt');
        $user2->setLocale('en');
        $contact2 = new Contact();
        $contact2->setFirstName('User2');
        $contact2->setLastName('Test');
        $user2->setContact($contact2);
        $this->em->persist($contact2);
        $this->user2 = $user2;
        $this->em->persist($this->user2);

        // User 3
        $user3 = new User();
        $user3->setUsername('user3');
        $user3->setEmail('user3@test.com');
        $user3->setPassword('securepassword');
        $user3->setSalt('salt');
        $user3->setLocale('en');
        $user3->setPasswordResetToken('thisisasupersecrettoken');
        $user3->setPasswordResetTokenExpiresAt((new \DateTime())->add(new \DateInterval('PT24H')));
        $user3->setPasswordResetTokenEmailsSent(1);
        $contact3 = new Contact();
        $contact3->setFirstName('User3');
        $contact3->setLastName('Test');
        $user3->setContact($contact3);
        $this->em->persist($contact3);
        $this->user3 = $user3;
        $this->em->persist($this->user3);

        $this->em->flush();
    }

    public function testSendEmailAction()
    {
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        $client->request('GET', '/security/reset/email', [
            'user' => $this->user1->getEmail(),
        ]);

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        // asserting response
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals($this->user1->getEmail(), $response->email);

        // asserting user properties
        $user = $client->getContainer()->get('doctrine')->getManager()->find('SuluSecurityBundle:User', $this->user1->getId());
        $this->assertTrue(is_string($user->getPasswordResetToken()));
        $this->assertGreaterThan(new \DateTime(), $user->getPasswordResetTokenExpiresAt());

        // asserting sent mail
        $this->assertEquals(1, $mailCollector->getMessageCount());
        $message = $mailCollector->getMessages()[0];
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals($user->getEmail(), key($message->getTo()));
        $this->assertContains($user->getPasswordResetToken(), $message->getBody());
    }

    public function testSendEmailActionWtihUsername()
    {
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        $client->request('GET', '/security/reset/email', [
            'user' => $this->user1->getUsername(),
        ]);

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        // asserting response
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals($this->user1->getEmail(), $response->email);

        // asserting user properties
        $user = $client->getContainer()->get('doctrine')->getManager()
            ->find('SuluSecurityBundle:User', $this->user1->getId());
        $this->assertTrue(is_string($user->getPasswordResetToken()));
        $this->assertGreaterThan(new \DateTime(), $user->getPasswordResetTokenExpiresAt());
        $this->assertEquals(1, $user->getPasswordResetTokenEmailsSent());

        // asserting sent mail
        $this->assertEquals(1, $mailCollector->getMessageCount());
        $message = $mailCollector->getMessages()[0];
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals($user->getEmail(), key($message->getTo()));
        $this->assertContains($user->getPasswordResetToken(), $message->getBody());
    }

    public function testSendEmailActionWithUserWithoutEmail()
    {
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        $client->request('GET', '/security/reset/email', [
            'user' => $this->user2->getUsername(),
        ]);

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        // asserting response
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals('installation.email@sulu.test', $response->email);

        // asserting user properties
        $user = $client->getContainer()->get('doctrine')->getManager()
            ->find('SuluSecurityBundle:User', $this->user2->getId());
        $this->assertTrue(is_string($user->getPasswordResetToken()));
        $this->assertGreaterThan(new \DateTime(), $user->getPasswordResetTokenExpiresAt());
        $this->assertEquals(1, $user->getPasswordResetTokenEmailsSent());

        // asserting sent mail
        $this->assertEquals(1, $mailCollector->getMessageCount());
        $message = $mailCollector->getMessages()[0];
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals('installation.email@sulu.test', key($message->getTo()));
        $this->assertContains($user->getPasswordResetToken(), $message->getBody());
    }

    public function testResendEmailAction()
    {
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        $client->request('GET', '/security/reset/email/resend', [
            'user' => $this->user3->getEmail(),
        ]);

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        // asserting response
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals($this->user3->getEmail(), $response->email);

        // asserting user properties
        $user = $client->getContainer()->get('doctrine')->getManager()
            ->find('SuluSecurityBundle:User', $this->user3->getId());
        $this->assertEquals('thisisasupersecrettoken', $user->getPasswordResetToken());
        $this->assertGreaterThan(new \DateTime(), $user->getPasswordResetTokenExpiresAt());
        $this->assertEquals(2, $user->getPasswordResetTokenEmailsSent());

        // asserting sent mail
        $this->assertEquals(1, $mailCollector->getMessageCount());
        $message = $mailCollector->getMessages()[0];
        $this->assertInstanceOf('Swift_Message', $message);
        $this->assertEquals($user->getEmail(), key($message->getTo()));
        $this->assertContains($user->getPasswordResetToken(), $message->getBody());
    }

    public function testResendEmailActionTooMuch()
    {
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        // these request should all work (starting counter at 1 - because user3 already has one sent email)
        $counter = 1;
        for (; $counter < ResettingController::MAX_NUMBER_EMAILS; ++$counter) {
            $client->request('GET', '/security/reset/email/resend', [
                'user' => $this->user3->getEmail(),
            ]);

            $mailCollector = $client->getProfile()->getCollector('swiftmailer');
            $response = json_decode($client->getResponse()->getContent());

            $this->assertHttpStatusCode(200, $client->getResponse());
            $this->assertEquals($this->user3->getEmail(), $response->email);
            $this->assertEquals(1, $mailCollector->getMessageCount());
        }

        // now this request should fail
        $client->request('GET', '/security/reset/email/resend', [
            'user' => $this->user3->getEmail(),
        ]);

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        $response = json_decode($client->getResponse()->getContent());
        $user = $client->getContainer()->get('doctrine')->getManager()
            ->find('SuluSecurityBundle:User', $this->user3->getId());

        $this->assertHttpStatusCode(400, $client->getResponse());
        $this->assertEquals(1007, $response->code);
        $this->assertEquals(0, $mailCollector->getMessageCount());
        $this->assertEquals($counter, $user->getPasswordResetTokenEmailsSent());
    }

    public function testSendEmailActionWithMissingUser()
    {
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        $client->request('GET', '/security/reset/email');

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $client->getResponse());
        $this->assertEquals(0, $response->code);
        $this->assertEquals(0, $mailCollector->getMessageCount());
    }

    public function testSendEmailActionWithNotExistingUser()
    {
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        $client->request('GET', '/security/reset/email', [
            'user' => 'lord.voldemort@askab.an',
        ]);

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        $this->assertHttpStatusCode(400, $client->getResponse());
        $this->assertEquals(0, $response->code);
        $this->assertEquals(0, $mailCollector->getMessageCount());
    }

    public function testSendEmailActionMultipleTimes()
    {
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        $client->request('GET', '/security/reset/email', [
            'user' => $this->user1->getUsername(),
        ]);
        $response = json_decode($client->getResponse()->getContent());
        // asserting response
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals($this->user1->getEmail(), $response->email);

        // second request should be blocked
        $client->request('GET', '/security/reset/email', [
            'user' => $this->user1->getUsername(),
        ]);
        $response = json_decode($client->getResponse()->getContent());
        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        // asserting response
        $this->assertHttpStatusCode(400, $client->getResponse());
        $this->assertEquals(1003, $response->code);
        $this->assertEquals(0, $mailCollector->getMessageCount());
    }

    public function testResetAction()
    {
        $client = $this->createAuthenticatedClient();
        $newPassword = 'anewpasswordishouldremeber';

        $client->request('GET', '/security/reset', [
            'token' => 'thisisasupersecrettoken',
            'password' => $newPassword,
        ]);
        $response = json_decode($client->getResponse()->getContent());
        $user = $client->getContainer()->get('doctrine')->getManager()
            ->find('SuluSecurityBundle:User', $this->user3->getId());

        $this->assertHttpStatusCode(200, $client->getResponse());

        $encoder = $this->getContainer()->get('security.encoder_factory')->getEncoder($user);
        $this->assertEquals($encoder->encodePassword($newPassword, $user->getSalt()), $user->getPassword());
        $this->assertNull($user->getPasswordResetToken());
        $this->assertNull($user->getPasswordResetTokenExpiresAt());
    }

    public function testResetActionWithoutToken()
    {
        $client = $this->createAuthenticatedClient();
        $passwordBefore = $this->user3->getPassword();

        $client->request('GET', '/security/reset', [
            'password' => 'thispasswordshouldnotbeapplied',
        ]);
        $response = json_decode($client->getResponse()->getContent());
        $user = $this->em->find('SuluSecurityBundle:User', $this->user3->getId());

        $this->assertHttpStatusCode(400, $client->getResponse());
        $this->assertEquals(1005, $response->code);
        $this->assertEquals($passwordBefore, $user->getPassword());
    }

    public function testResetActionWithInvalidToken()
    {
        $client = $this->createAuthenticatedClient();
        $passwordBefore = $this->user3->getPassword();

        $client->request('GET', '/security/reset', [
            'token' => 'thistokendoesnotexist',
            'password' => 'thispasswordshouldnotbeapplied',
        ]);
        $response = json_decode($client->getResponse()->getContent());
        $user = $this->em->find('SuluSecurityBundle:User', $this->user3->getId());

        $this->assertHttpStatusCode(400, $client->getResponse());
        $this->assertEquals(1005, $response->code);
        $this->assertEquals($passwordBefore, $user->getPassword());
    }
}
