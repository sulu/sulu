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

use Sulu\Bundle\SecurityBundle\Command\CreateRoleCommand;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateRoleCommandTest extends SuluTestCase
{
    /**
     * @var CommandTester
     */
    private $tester;

    public function setUp(): void
    {
        $this->purgeDatabase();
        $application = new Application($this->getContainer()->get('kernel'));

        $createUserCommand = new CreateRoleCommand(
            $this->getContainer()->get('doctrine.orm.entity_manager'),
            $this->getContainer()->get('sulu.repository.role'),
            $this->getContainer()->get('sulu_admin.admin_pool')
        );
        $createUserCommand->setApplication($application);
        $this->tester = new CommandTester($createUserCommand);
    }

    public function testCreateRole(): void
    {
        $this->createRole('foo');
        $this->assertEquals('Created role "foo" in system "Sulu".' . \PHP_EOL, $this->tester->getDisplay());
    }

    public function testCreateRoleAlreadyExisting(): void
    {
        $this->createRole('foo');
        $this->createRole('foo');

        $this->assertEquals('Role "foo" already exists.' . \PHP_EOL, $this->tester->getDisplay());
    }

    private function createRole($name): void
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
