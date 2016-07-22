<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Persistence\Tests\Functional\EventSubscriber\ORM;

use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\User as SymfonyUser;

class UserBlameSubscriberIntegrationTest extends SuluTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->initOrm();
    }

    protected function initOrm()
    {
        $this->purgeDatabase();
    }

    public function testUserBlame()
    {
        $tokenStorage = $this->getContainer()->get('security.token_storage');
        $token = new UsernamePasswordToken('test', 'test', 'test_provider', []);
        $user = new User();
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
        $contact->setPosition('CEO');
        $contact->setSalutation('Sehr geehrter Herr Dr Mustermann');
        $this->getEntityManager()->persist($contact);
        $this->getEntityManager()->flush();

        $changer = $contact->getChanger();
        $creator = $contact->getCreator();

        $this->assertSame($changer, $user);
        $this->assertSame($creator, $user);
    }

    public function testExternalUserBlame()
    {
        $this->createExternalUser();

        $contact = new Contact();
        $contact->setFirstName('Max');
        $contact->setLastName('Mustermann');
        $contact->setPosition('CEO');
        $contact->setSalutation('Sehr geehrter Herr Dr Mustermann');

        $this->setExpectedExceptionRegExp(
            'RuntimeException',
            '/Expected user object to be an instance of \(Sulu\) UserInterface\./'
        );

        $this->getEntityManager()->persist($contact);
        $this->getEntityManager()->flush();
    }

    public function testExternalUserNoBlame()
    {
        $this->createExternalUser();

        $permission = new Permission();
        $permission->setContext('sulu.contact.people');
        $permission->setPermissions(127);
        $this->getEntityManager()->persist($permission);
        $this->getEntityManager()->flush();

        $this->assertInternalType('int', $permission->getId());
    }

    private function createExternalUser()
    {
        $tokenStorage = $this->getContainer()->get('security.token_storage');
        $token = new UsernamePasswordToken('test', 'test', 'test_provider', []);
        $user = new SymfonyUser('test', 'test');
        $token->setUser($user);
        $tokenStorage->setToken($token);
    }
}
