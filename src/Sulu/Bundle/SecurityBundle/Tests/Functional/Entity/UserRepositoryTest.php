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

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\ContactBundle\Entity\Email;
use Sulu\Bundle\ContactBundle\Entity\EmailType;
use Sulu\Bundle\SecurityBundle\Entity\Group;
use Sulu\Bundle\SecurityBundle\Entity\Permission;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\SecurityBundle\Entity\UserRepository;
use Sulu\Bundle\SecurityBundle\Entity\UserRole;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class UserRepositoryTest extends SuluTestCase
{
    /**
     * @var EntityManager
     */
    private $em;

    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();

        // email
        $emailType = new EmailType();
        $emailType->setName('Private');
        $this->em->persist($emailType);

        $email = new Email();
        $email->setEmail('max.mustermann@muster.at');
        $email->setEmailType($emailType);
        $this->em->persist($email);

        $email2 = new Email();
        $email2->setEmail('maria.musterfrau@muster.at');
        $email2->setEmailType($emailType);
        $this->em->persist($email2);

        // Contact

        $contact1 = new Contact();
        $contact1->setFirstName('Max');
        $contact1->setLastName('Muster');
        $contact1->addEmail($email);
        $this->em->persist($contact1);

        $contact2 = new Contact();
        $contact2->setFirstName('Maria');
        $contact2->setLastName('Musterfrau');
        $contact2->addEmail($email2);
        $this->em->persist($contact2);

        $this->em->flush();

        $role1 = new Role();
        $role1->setName('Role1');
        $role1->setSystem('Sulu');
        $this->em->persist($role1);

        $role2 = new Role();
        $role2->setName('Role2');
        $role2->setSystem('Test');
        $this->em->persist($role2);

        // User 1
        $user = new User();
        $user->setUsername('admin');
        $user->setEmail('user1@test.com');
        $user->setPassword('securepassword');
        $user->setPasswordResetToken('mySuperSecretToken');
        $user->setSalt('salt');
        $user->setLocale('de');
        $user->setContact($contact1);
        $this->em->persist($user);

        // User 2
        $user2 = new User();
        $user2->setUsername('test');
        $user2->setEmail('user2@test.com');
        $user2->setPassword('securepassword');
        $user2->setSalt('salt');
        $user2->setLocale('de');
        $user2->setContact($contact2);
        $this->em->persist($user2);

        $this->em->flush();

        $userRole1 = new UserRole();
        $userRole1->setRole($role1);
        $userRole1->setUser($user);
        $userRole1->setLocale(json_encode(['de', 'en']));
        $this->em->persist($userRole1);

        $userRole2 = new UserRole();
        $userRole2->setRole($role2);
        $userRole2->setUser($user2);
        $userRole2->setLocale(json_encode(['de', 'en']));
        $this->em->persist($userRole2);

        $permission1 = new Permission();
        $permission1->setPermissions(122);
        $permission1->setRole($role1);
        $permission1->setContext('Context 1');
        $this->em->persist($permission1);

        $permission2 = new Permission();
        $permission2->setPermissions(122);
        $permission2->setRole($role2);
        $permission2->setContext('Context 2');
        $this->em->persist($permission2);

        // user groups
        $group1 = new Group();
        $group1->setName('Group1');
        $group1->setLft(0);
        $group1->setRgt(0);
        $group1->setDepth(0);
        $this->em->persist($group1);

        $group2 = new Group();
        $group2->setName('Group2');
        $group2->setLft(0);
        $group2->setRgt(0);
        $group2->setDepth(0);
        $this->em->persist($group2);

        $this->em->flush();
    }

    public function testFindUserByEmail()
    {
        $this->prepareUser('Sulu', 'sulu', 'sulu');

        $client = $this->createAuthenticatedClient();

        /** @var UserRepository $userRepository */
        $userRepository = $client->getContainer()->get('sulu_security.user_repository');

        $user = $userRepository->findUserByEmail('user2@test.com');

        $this->assertEquals('user2@test.com', $user->getEmail());
        $this->assertEquals('test', $user->getUsername());
    }

    public function testFindUserWithSecurityByIdentifier()
    {
        $this->prepareUser('Sulu', 'sulu', 'sulu');

        $client = $this->createAuthenticatedClient();

        /** @var UserRepository $userRepository */
        $userRepository = $client->getContainer()->get('sulu_security.user_repository');

        $userByMail = $userRepository->findUserByIdentifier('user2@test.com');
        $userByUsername = $userRepository->findUserByIdentifier('test');

        $this->assertEquals('user2@test.com', $userByMail->getEmail());
        $this->assertEquals('test', $userByMail->getUsername());
        $this->assertEquals('user2@test.com', $userByUsername->getEmail());
        $this->assertEquals('test', $userByUsername->getUsername());
    }

    public function testFindUserByToken()
    {
        $this->prepareUser('Sulu', 'sulu', 'sulu');

        $client = $this->createAuthenticatedClient();

        /** @var UserRepository $userRepository */
        $userRepository = $client->getContainer()->get('sulu_security.user_repository');

        $user = $userRepository->findUserByToken('mySuperSecretToken');

        $this->assertEquals('user1@test.com', $user->getEmail());
        $this->assertEquals('admin', $user->getUsername());
    }

    public function testFindUserBySystem()
    {
        $this->prepareUser('Sulu Role 2', 'sulu', 'sulu');
        $this->prepareUser('Client Role', 'client', 'client', true, false, 'Client');

        $client = $this->createAuthenticatedClient();
        /** @var UserRepository $userRepository */
        $userRepository = $client->getContainer()->get('sulu_security.user_repository');

        $suluUsers = $userRepository->findUserBySystem('Sulu');
        $this->assertCount(2, $suluUsers);
        $this->assertEquals('admin', $suluUsers[0]->getUsername());
        $this->assertEquals('sulu', $suluUsers[1]->getUsername());

        $clientUsers = $userRepository->findUserBySystem('Client');
        $this->assertCount(1, $clientUsers);
        $this->assertEquals('client', $clientUsers[0]->getUsername());
    }

    private function prepareUser($roleName, $username, $password, $enabled = true, $locked = false, $system = 'Sulu')
    {
        $emailType = new EmailType();
        $emailType->setName('Private');
        $this->em->persist($emailType);

        $email = new Email();
        $email->setEmail('max.mustermann@muster.at');
        $email->setEmailType($emailType);
        $this->em->persist($email);

        $contact1 = new Contact();
        $contact1->setFirstName('Max');
        $contact1->setLastName('Muster');
        $contact1->addEmail($email);
        $this->em->persist($contact1);

        $user = new User();
        $user->setUsername($username);
        $user->setPassword($password);
        $user->setSalt('salt');
        $user->setLocale('de');
        $user->setContact($contact1);
        $user->setEnabled($enabled);
        $user->setLocked($locked);
        $this->em->persist($user);

        $role = new Role();
        $role->setName($roleName);
        $role->setSystem($system);
        $this->em->persist($role);

        $userRole = new UserRole();
        $userRole->setRole($role);
        $userRole->setUser($user);
        $userRole->setLocale('');
        $this->em->persist($userRole);

        $this->em->flush();

        return $user;
    }
}
