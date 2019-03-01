<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Command;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ContentTypesDumpCommandTest extends SuluTestCase
{
    /**
     * @var CommandTester
     */
    private $tester;

    public function setUp()
    {
        $application = new Application($this->getContainer()->get('kernel'));

        $command = new ContentTypesDumpCommand();
        $command->setApplication($application);
        $command->setContainer($this->getContainer());
        $this->tester = new CommandTester($command);
    }

    public function testExecute()
    {
        $this->tester->execute([]);

        $output = $this->tester->getDisplay();

        $this->assertContains('text_line', $output);
        $this->assertContains('text_area', $output);
        $this->assertContains('text_editor', $output);
    }
}
