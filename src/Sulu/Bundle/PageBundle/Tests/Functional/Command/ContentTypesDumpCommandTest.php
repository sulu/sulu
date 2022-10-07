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

use Sulu\Bundle\PageBundle\Command\ContentTypesDumpCommand;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\Command\ContainerDebugCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ContentTypesDumpCommandTest extends SuluTestCase
{
    /**
     * @var CommandTester
     */
    private $tester;

    public function setUp(): void
    {
        $application = new Application($this->getContainer()->get('kernel'));

        $debugContainerCommand = new ContainerDebugCommand();
        $debugContainerCommand->setApplication($application);
        $application->add($debugContainerCommand);

        $command = new ContentTypesDumpCommand();
        $command->setApplication($application);
        $this->tester = new CommandTester($command);
    }

    public function testExecute(): void
    {
        $this->tester->execute([]);

        $output = $this->tester->getDisplay();

        $this->assertStringContainsString('block', $output);
        $this->assertStringContainsString('checkbox', $output);
        $this->assertStringContainsString('color', $output);
        $this->assertStringContainsString('date', $output);
        $this->assertStringContainsString('email', $output);
        $this->assertStringContainsString('page_selection', $output);
        $this->assertStringContainsString('select', $output);
        $this->assertStringContainsString('number', $output);
        $this->assertStringContainsString('password', $output);
        $this->assertStringContainsString('phone', $output);
        $this->assertStringContainsString('resource_locator', $output);
        $this->assertStringContainsString('single_page_selection', $output);
        $this->assertStringContainsString('single_select', $output);
        $this->assertStringContainsString('text_area', $output);
        $this->assertStringContainsString('text_editor', $output);
        $this->assertStringContainsString('text_line', $output);
        $this->assertStringContainsString('time', $output);
        $this->assertStringContainsString('url', $output);
        $this->assertStringContainsString('target_group_selection', $output);
        $this->assertStringContainsString('category_selection', $output);
        $this->assertStringContainsString('contact', $output);
        $this->assertStringContainsString('smart_content', $output);
        $this->assertStringContainsString('teaser_selection', $output);
        $this->assertStringContainsString('location', $output);
        $this->assertStringContainsString('media_selection', $output);
        $this->assertStringContainsString('route', $output);
        $this->assertStringContainsString('snippet_selection', $output);
        $this->assertStringContainsString('tag_selection', $output);
    }
}
