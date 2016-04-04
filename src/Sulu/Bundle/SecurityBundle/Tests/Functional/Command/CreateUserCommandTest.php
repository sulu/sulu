<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Command;

use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;
use Sulu\Bundle\SecurityBundle\Entity\Role;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateUserCommandTest extends SuluTestCase
{
    /**
     * @var CommandTester
     */
    private $tester;

    /**
     * @var CreateUserCommand
     */
    private $command;

    public function setUp()
    {
        $application = new Application($this->getContainer()->get('kernel'));

        $loadFixturesCommand = new LoadDataFixturesDoctrineCommand();
        $loadFixturesCommand->setApplication($application);
        $loadFixturesCommandTester = new CommandTester($loadFixturesCommand);
        $loadFixturesCommandTester->execute([], ['interactive' => false]);

        $this->command = new CreateUserCommand();
        $this->command->setApplication($application);
        $this->tester = new CommandTester($this->command);
    }

    public function testCreateUser()
    {
        $this->createRole('test');
        $this->createUser('sulu', 'test');
        $this->assertEquals('Created user "sulu" in role "test"' . PHP_EOL, $this->tester->getDisplay());
    }

    public function testCreateUserAlreadyExists()
    {
        $this->createRole('test');
        $this->createUser('sulu', 'test');
        $this->createUser('sulu', 'test');

        $this->assertEquals('User "sulu" already exists', trim($this->tester->getDisplay()));
    }

    public function testCreateUserNonExistingRole()
    {
        $this->createRole('test');
        $this->createUser('sulu', 'testfoobar');
        $this->assertEquals('Role "testfoobar" not found. The following roles are available: "test"' . PHP_EOL, $this->tester->getDisplay());
    }

    public function testCreateUserNonExistingLocale()
    {
        $this->createRole('test');
        $this->createUser('sulu', 'test', 'ax');
        $this->assertContains('Given locale "ax" is invalid, must be one of "', $this->tester->getDisplay());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The system currently has no roles. Use the "sulu:security:role:create" command to create roles.
     */
    public function testCreateUserNoRoles()
    {
        $this->createUser('sulu', 'blah');
    }

    private function createRole($roleName)
    {
        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $now = new \Datetime();

        $role = new Role();
        $role->setName($roleName);
        $role->setSystem('Sulu');

        $em->persist($role);
        $em->flush();
    }

    private function createUser($username, $role, $locale = 'en')
    {
        $this->tester->execute(
            [
                'username' => 'sulu',
                'firstName' => 'Sulu',
                'lastName' => 'Hikaru',
                'email' => 'sulu.hikaru@startrek.com',
                'locale' => $locale,
                'role' => $role,
                'password' => 'sulu',
            ],
            ['interactive' => false]
        );
    }
}
