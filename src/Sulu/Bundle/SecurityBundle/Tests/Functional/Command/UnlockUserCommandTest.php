<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Tests\Functional\Command;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ContactBundle\Entity\Contact;
use Sulu\Bundle\SecurityBundle\Entity\User;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Security\Authentication\UserInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class UnlockUserCommandTest extends SuluTestCase
{
    private CommandTester $tester;

    private EntityManagerInterface $em;

    public function setUp(): void
    {
        $this->purgeDatabase();
        $this->em = $this->getEntityManager();

        $application = new Application($this->getContainer()->get('kernel'));
        $command = $this->getContainer()->get('sulu_security.command.unlock_user');
        $command->setApplication($application);
        $this->tester = new CommandTester($command);
    }

    public function testUnlockUser(): void
    {
        $this->createUser('john', true);

        $this->tester->execute(['identifier' => 'john'], ['interactive' => false]);

        $this->tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('User "john" has been unlocked.', $this->tester->getDisplay());
        $this->assertFalse($this->findUser('john')->getLocked());
    }

    public function testUnlockUserByEmail(): void
    {
        $this->createUser('john', true);

        $this->tester->execute(['identifier' => 'john@example.com'], ['interactive' => false]);

        $this->tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('User "john@example.com" has been unlocked.', $this->tester->getDisplay());
        $this->assertFalse($this->findUser('john')->getLocked());
    }

    public function testUnlockAlreadyUnlockedUser(): void
    {
        $this->createUser('john');

        $this->tester->execute(['identifier' => 'john'], ['interactive' => false]);

        $this->tester->assertCommandIsSuccessful();
        $this->assertStringContainsString('User "john" is already unlocked, doing nothing.', $this->tester->getDisplay());
        $this->assertFalse($this->findUser('john')->getLocked());
    }

    public function testUnlockNonExistingUser(): void
    {
        $this->tester->execute(['identifier' => 'ghost'], ['interactive' => false]);

        $this->assertSame(1, $this->tester->getStatusCode());
        $this->assertStringContainsString('User "ghost" not found.', $this->tester->getDisplay());
    }

    private function createUser(string $username, bool $locked = false): void
    {
        $contact = new Contact();
        $contact->setFirstName('John');
        $contact->setLastName('Doe');
        $this->em->persist($contact);

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($username . '@example.com');
        $user->setPassword('securepassword');
        $user->setSalt('salt');
        $user->setLocale('en');
        $user->setLocked($locked);
        $user->setContact($contact);
        $this->em->persist($user);

        $this->em->flush();
        $this->em->clear();
    }

    private function findUser(string $username): UserInterface
    {
        return $this->getContainer()->get('sulu.repository.user')->findUserByUsername($username);
    }
}
