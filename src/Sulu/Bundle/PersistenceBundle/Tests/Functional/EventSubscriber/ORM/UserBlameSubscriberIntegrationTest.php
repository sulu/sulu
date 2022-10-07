<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PersistenceBundle\Tests\Functional\EventSubscriber\ORM;

use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;

class UserBlameSubscriberIntegrationTest extends SuluTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->initOrm();
    }

    protected function initOrm()
    {
        $this->purgeDatabase();
    }

    public function testUserBlame(): void
    {
        $tokenStorage = $this->getContainer()->get('security.token_storage');
        $user = new User();
        $token = new UsernamePasswordToken($user, 'test', []);
        $user->setUsername('dantleech');
        $user->setPassword('foo');
        $user->setLocale('fr');
        $user->setSalt('saltz');
        $contact = new Contact();
        $contact->setFirstName('Daniel');
        $contact->setLastName('Leech');
        $user->setContact($contact);
        $this->getEntityManager()->persist($contact);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $token->setUser($user);

        $tokenStorage->setToken($token);
        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $contact->setSalutation('Sehr geehrter Herr Dr Mustermann');
        $this->getEntityManager()->persist($contact);
        $this->getEntityManager()->flush();

        $this->assertSame($user, $contact->getChanger());
        $this->assertSame($user, $contact->getCreator());

        $contact->setCreator(null);
        $this->getEntityManager()->flush();

        $this->assertSame($user, $contact->getChanger());
        $this->assertNull($contact->getCreator());
    }

    public function testExternalUserBlame(): void
    {
        $this->createExternalUser();

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $contact->setSalutation('Sehr geehrter Herr Dr Mustermann');

        $this->getEntityManager()->persist($contact);
        $this->getEntityManager()->flush();

        $this->assertNull($contact->getCreator());
        $this->assertNull($contact->getChanger());
    }

    public function testExternalUserNoBlame(): void
    {
        $this->createExternalUser();

        $permission = new Permission();
        $permission->setContext('sulu.contact.people');
        $permission->setPermissions(127);
        $this->getEntityManager()->persist($permission);
        $this->getEntityManager()->flush();

        $this->assertIsInt($permission->getId());
    }

    public function testSetUserBlame(): void
    {
        $tokenStorage = $this->getContainer()->get('security.token_storage');
        $user = new User();
        $token = new UsernamePasswordToken($user, 'test', []);
        $user->setUsername('dantleech');
        $user->setPassword('foo');
        $user->setLocale('fr');
        $user->setSalt('saltz');
        $contact = new Contact();
        $contact->setFirstName('Daniel');
        $contact->setLastName('Leech');
        $user->setContact($contact);
        $this->getEntityManager()->persist($contact);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
        $token->setUser($user);

        $tokenStorage->setToken($token);

        $otherUser = new User();
        $otherUser->setUsername('johannes');
        $otherUser->setPassword('foo');
        $otherUser->setLocale('fr');
        $otherUser->setSalt('saltz');
        $contact = new Contact();
        $contact->setFirstName('Daniel');
        $contact->setLastName('Leech');
        $otherUser->setContact($contact);
        $this->getEntityManager()->persist($contact);
        $this->getEntityManager()->persist($otherUser);
        $this->getEntityManager()->flush();

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $contact->setSalutation('Sehr geehrter Herr Dr Mustermann');
        $contact->setCreator($otherUser);
        $contact->setChanger($otherUser);
        $this->getEntityManager()->persist($contact);
        $this->getEntityManager()->flush();

        $this->assertSame($contact->getCreator(), $otherUser);
        $this->assertSame($contact->getChanger(), $otherUser);
    }

    private function createExternalUser(): void
    {
        $tokenStorage = $this->getContainer()->get('security.token_storage');
        $user = new InMemoryUser('test', 'test');
        $token = new UsernamePasswordToken($user, 'test', []);
        $token->setUser($user);
        $tokenStorage->setToken($token);
    }
}
