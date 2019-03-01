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
use Sulu\Component\DocumentManager\DocumentManager;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ValidateWebspacesCommandTest extends SuluTestCase
{
    /**
     * @var CommandTester
     */
    private $tester;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    public function setUp()
    {
        $application = new Application($this->getContainer()->get('kernel'));
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');

        $command = new ValidateWebspacesCommand();
        $command->setApplication($application);
        $command->setContainer($this->getContainer());
        $this->tester = new CommandTester($command);
    }

    public function testExecute()
    {
        $this->tester->execute([]);
        $output = $this->tester->getDisplay();

        $this->assertContains('sulu_io', $output);
        $this->assertContains('Default Templates:', $output);
        $this->assertContains('Page Templates:', $output);
        $this->assertContains('Templates:', $output);
        $this->assertContains('Localizations:', $output);
    }
}
