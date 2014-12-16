<?php
/*
 * This file is part of the Sulu CMF.
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
        $loadFixturesCommandTester->execute(array(), array('interactive' => false));

        $createUserCommand = new CreateRoleCommand();
        $createUserCommand->setApplication($application);
        $this->tester = new CommandTester($createUserCommand);
    }

    public function testExecute()
    {
        $this->tester->execute(
            array(
                'name' => 'test',
                'system' => 'Sulu'
            ),
            array('interactive' => false)
        );

        $this->assertEquals("Created role test in system Sulu\n", $this->tester->getDisplay());
    }
}
