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
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class ResettingControllerTest extends SuluTestCase
{
    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @var User[]
     */
    private $users = [];

    /**
     * @var Role
     */
    private $role;

    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();

        $this->role = $this->createRole('Sulu');
        $this->em->persist($this->role);

        // User 1
        $this->users[] = $user = $this->createUser(1, 'user1@test.com');
        $this->em->persist($user);
        $this->em->persist($this->createUserRole($user, $this->role));

        // User 2
        $this->users[] = $user = $this->createUser(2);
        $this->em->persist($user);
        $this->em->persist($this->createUserRole($user, $this->role));

        // User 3
        $this->users[] = $user = $this->createUser(3, 'user3@test.com');
        $user->setPasswordResetToken('thisisasupersecrettoken');
        $user->setPasswordResetTokenExpiresAt((new \DateTime())->add(new \DateInterval('PT24H')));
        $user->setPasswordResetTokenEmailsSent(1);
        $this->em->persist($user);
        $this->em->persist($this->createUserRole($user, $this->role));

        $this->em->flush();
    }

    public function testSendEmailAction()
    {
        $client = $this->createAuthenticatedClient();
        $client->enableProfiler();

        $client->request('GET', '/security/reset/email', [
            'user' => $this->users[0]->getEmail(),
        ]);

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        // asserting response
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals($this->users[0]->getEmail(), $response->email);

        // asserting user properties
        $user = $client->getContainer()->get('doctrine')->getManager()->find(
            'SuluSecurityBundle:User',
            $this->users[0]->getId()
        );
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
            'user' => $this->users[0]->getUsername(),
        ]);

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        // asserting response
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals($this->users[0]->getEmail(), $response->email);

        // asserting user properties
        $user = $client->getContainer()->get('doctrine')->getManager()->find(
                'SuluSecurityBundle:User',
                $this->users[0]->getId()
            );
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
            'user' => $this->users[1]->getUsername(),
        ]);

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        // asserting response
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals('installation.email@sulu.test', $response->email);

        // asserting user properties
        $user = $client->getContainer()->get('doctrine')->getManager()->find(
                'SuluSecurityBundle:User',
                $this->users[1]->getId()
            );
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
            'user' => $this->users[2]->getEmail(),
        ]);

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $response = json_decode($client->getResponse()->getContent());

        // asserting response
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals($this->users[2]->getEmail(), $response->email);

        // asserting user properties
        $user = $client->getContainer()->get('doctrine')->getManager()->find(
                'SuluSecurityBundle:User',
                $this->users[2]->getId()
            );
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
                'user' => $this->users[2]->getEmail(),
            ]);

            $mailCollector = $client->getProfile()->getCollector('swiftmailer');
            $response = json_decode($client->getResponse()->getContent());

            $this->assertHttpStatusCode(200, $client->getResponse());
            $this->assertEquals($this->users[2]->getEmail(), $response->email);
            $this->assertEquals(1, $mailCollector->getMessageCount());
        }

        // now this request should fail
        $client->request('GET', '/security/reset/email/resend', [
            'user' => $this->users[2]->getEmail(),
        ]);

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');
        $response = json_decode($client->getResponse()->getContent());
        $user = $client->getContainer()->get('doctrine')->getManager()->find(
                'SuluSecurityBundle:User',
                $this->users[2]->getId()
            );

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
            'user' => $this->users[0]->getUsername(),
        ]);
        $response = json_decode($client->getResponse()->getContent());
        // asserting response
        $this->assertHttpStatusCode(200, $client->getResponse());
        $this->assertEquals($this->users[0]->getEmail(), $response->email);

        // second request should be blocked
        $client->request('GET', '/security/reset/email', [
            'user' => $this->users[0]->getUsername(),
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
        $user = $client->getContainer()->get('doctrine')->getManager()->find(
                'SuluSecurityBundle:User',
                $this->users[2]->getId()
            );

        $this->assertHttpStatusCode(200, $client->getResponse());

        $encoder = $this->getContainer()->get('security.encoder_factory')->getEncoder($user);
        $this->assertEquals($encoder->encodePassword($newPassword, $user->getSalt()), $user->getPassword());
        $this->assertNull($user->getPasswordResetToken());
        $this->assertNull($user->getPasswordResetTokenExpiresAt());
    }

    public function testResetActionWithoutToken()
    {
        $client = $this->createAuthenticatedClient();
        $passwordBefore = $this->users[2]->getPassword();

        $client->request('GET', '/security/reset', [
            'password' => 'thispasswordshouldnotbeapplied',
        ]);
        $response = json_decode($client->getResponse()->getContent());
        $user = $this->em->find('SuluSecurityBundle:User', $this->users[2]->getId());

        $this->assertHttpStatusCode(400, $client->getResponse());
        $this->assertEquals(1005, $response->code);
        $this->assertEquals($passwordBefore, $user->getPassword());
    }

    public function testResetActionWithInvalidToken()
    {
        $client = $this->createAuthenticatedClient();
        $passwordBefore = $this->users[2]->getPassword();

        $client->request('GET', '/security/reset', [
            'token' => 'thistokendoesnotexist',
            'password' => 'thispasswordshouldnotbeapplied',
        ]);
        $response = json_decode($client->getResponse()->getContent());
        $user = $this->em->find('SuluSecurityBundle:User', $this->users[2]->getId());

        $this->assertHttpStatusCode(400, $client->getResponse());
        $this->assertEquals(1005, $response->code);
        $this->assertEquals($passwordBefore, $user->getPassword());
    }

    public function testResetActionNoRole()
    {
        $user = $this->createUser(4);
        $this->em->persist($user);
        $this->em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/security/reset/email', [
            'user' => $user->getUsername(),
        ]);
        $this->assertHttpStatusCode(400, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1009, $response['code']);
    }

    public function testResetActionDifferentSystem()
    {
        $role = $this->createRole('Website');
        $this->em->persist($role);
        $this->em->flush();

        $user = $this->createUser(4);
        $this->em->persist($user);

        $userRole = $this->createUserRole($user, $role);
        $this->em->persist($userRole);

        $this->em->flush();

        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/security/reset/email', [
            'user' => $user->getUsername(),
        ]);
        $this->assertHttpStatusCode(400, $client->getResponse());

        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1009, $response['code']);
    }

    protected function getExpectedEmailData(Client $client, User $user)
    {
        $sender = $this->getContainer()->getParameter('sulu_security.reset_password.mail.sender');
        $template = $this->getContainer()->getParameter('sulu_security.reset_password.mail.template');
        $resetUrl = $this->getContainer()->get('router')->generate('sulu_admin.reset', [
            'token' => $user->getPasswordResetToken(),
        ], \Symfony\Component\Routing\Router::ABSOLUTE_URL);
        $body = $this->getContainer()->get('templating')->render($template, [
            'user' => $user,
            'reset_url' => $resetUrl,
            'translation_domain' => $this->getContainer()->getParameter('sulu_security.reset_password.mail.translation_domain'),
        ]);

        return [
            'subject' => $this->getContainer()->getParameter('sulu_security.reset_password.mail.subject'),
            'body' => trim($body),
            'sender' => $sender ? $sender : 'no-reply@' . $client->getRequest()->getHost(),
        ];
    }

    protected function createRole($system)
    {
        $role = new Role();
        $role->setName($system);
        $role->setSystem($system);

        return $role;
    }

    protected function createUser($index, $email = null)
    {
        $user = new User();
        $user->setUsername('user' . $index);
        $user->setEmail($email);
        $user->setPassword('securepassword');
        $user->setSalt('salt');
        $user->setLocale('en');

        $contact = new Contact();
        $contact->setFirstName('User' . $index);
        $contact->setLastName('Test');
        $user->setContact($contact);
        $this->em->persist($contact);

        return $user;
    }

    protected function createUserRole(User $user, Role $role)
    {
        $userRole = new UserRole();
        $userRole->setLocale(json_encode(['de']));
        $userRole->setRole($role);
        $userRole->setUser($user);

        return $userRole;
    }
}
