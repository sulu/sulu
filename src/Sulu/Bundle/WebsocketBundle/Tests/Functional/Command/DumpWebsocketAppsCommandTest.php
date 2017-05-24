<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsocketBundle\Command;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DumpWebsocketAppsCommandTest extends SuluTestCase
{
    /**
     * @var CommandTester
     */
    private $tester;

    public function setUp()
    {
        $application = new Application($this->getContainer()->get('kernel'));
        $command = new DumpWebsocketAppsCommand();
        $command->setApplication($application);
        $command->setContainer($this->getContainer());
        $this->tester = new CommandTester($command);
    }

    public function testExecute()
    {
        $this->tester->execute([]);

        $output = $this->tester->getDisplay();

        $this->assertContains('admin', $output);
        $this->assertContains('localhost', $output);
    }
}
