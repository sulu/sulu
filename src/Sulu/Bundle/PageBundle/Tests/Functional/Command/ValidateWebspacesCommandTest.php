<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Command;

use Sulu\Bundle\PageBundle\Command\ValidateWebspacesCommand;
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

    public function setUp(): void
    {
        $application = new Application();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $controllerNameParser = null;
        if ($this->getContainer()->has('sulu_page.controller_name_converter')) {
            /** @var \Symfony\Bundle\FrameworkBundle\Controller\ControllerNameParser $controllerNameParser */
            $controllerNameParser = $this->getContainer()->get('sulu_page.controller_name_converter');
        }

        $command = new ValidateWebspacesCommand(
            $this->getContainer()->get('twig'),
            $this->getContainer()->get('sulu_page.structure.factory'),
            $controllerNameParser,
            $this->getContainer()->get('sulu.content.structure_manager'),
            $this->getContainer()->get('sulu.content.webspace_structure_provider'),
            $this->getContainer()->get('sulu_core.webspace.webspace_manager')
        );
        $command->setApplication($application);
        $this->tester = new CommandTester($command);
    }

    public function testExecute(): void
    {
        $this->tester->execute([]);
        $output = $this->tester->getDisplay();

        $this->assertStringContainsString('sulu_io', $output);
        $this->assertStringContainsString('Default Templates:', $output);
        $this->assertStringContainsString('Page Templates:', $output);
        $this->assertStringContainsString('Templates:', $output);
        $this->assertStringContainsString('Localizations:', $output);
    }
}
