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
use Sulu\Bundle\SecurityBundle\Entity\Role;

class CreateUserCommandTest extends SuluTestCase
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

        $createUserCommand = new CreateUserCommand();
        $createUserCommand->setApplication($application);
        $this->tester = new CommandTester($createUserCommand);

        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();
        $now = new \Datetime();

        $role = new Role();
        $role->setName('test');
        $role->setSystem('Sulu');
        $role->setCreated($now);
        $role->setChanged($now);

        $em->persist($role);
        $em->flush();
    }

    public function testExecute()
    {
        $this->tester->execute(
            array(
                'username' => 'sulu',
                'firstName' => 'Sulu',
                'lastName' => 'Hikaru',
                'email' => 'sulu.hikaru@startrek.com',
                'locale' => 'en',
                'role' => 'test',
                'password' => 'sulu'
            ),
            array('interactive' => false)
        );

        $this->assertEquals("Created user sulu in role test\n", $this->tester->getDisplay());
    }
}
