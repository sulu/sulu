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
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateRoleCommandTest extends SuluTestCase
{
    /**
     * @var CommandTester
     */
    private $tester;

    public function setUp()
    {
        $application = new Application($this->getContainer()->get('kernel'));

        $loadFixturesCommand = new LoadDataFixturesDoctrineCommand();
        $loadFixturesCommand->setApplication($application);
        $loadFixturesCommandTester = new CommandTester($loadFixturesCommand);
        $loadFixturesCommandTester->execute([], ['interactive' => false]);

        $createUserCommand = new CreateRoleCommand();
        $createUserCommand->setApplication($application);
        $this->tester = new CommandTester($createUserCommand);
    }

    public function testCreateRole()
    {
        $this->createRole('foo');
        $this->assertEquals('Created role "foo" in system "Sulu".' . PHP_EOL, $this->tester->getDisplay());
    }

    public function testCreateRoleAlreadyExisting()
    {
        $this->createRole('foo');
        $this->createRole('foo');

        $this->assertEquals('Role "foo" already exists.' . PHP_EOL, $this->tester->getDisplay());
    }

    private function createRole($name)
    {
        $this->tester->execute(
            [
                'name' => $name,
                'system' => 'Sulu',
            ],
            ['interactive' => false]
        );
    }
}
