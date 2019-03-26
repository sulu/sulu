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

    public function setUp()
    {
        $application = new Application($this->getContainer()->get('kernel'));

        $debugContainerCommand = new ContainerDebugCommand();
        $debugContainerCommand->setApplication($application);
        $application->add($debugContainerCommand);

        $command = new ContentTypesDumpCommand();
        $command->setApplication($application);
        $this->tester = new CommandTester($command);
    }

    public function testExecute()
    {
        $this->tester->execute([]);

        $output = $this->tester->getDisplay();

        $this->assertContains('block', $output);
        $this->assertContains('checkbox', $output);
        $this->assertContains('color', $output);
        $this->assertContains('date', $output);
        $this->assertContains('email', $output);
        $this->assertContains('page_selection', $output);
        $this->assertContains('select', $output);
        $this->assertContains('number', $output);
        $this->assertContains('password', $output);
        $this->assertContains('phone', $output);
        $this->assertContains('resource_locator', $output);
        $this->assertContains('single_page_selection', $output);
        $this->assertContains('single_select', $output);
        $this->assertContains('text_area', $output);
        $this->assertContains('text_editor', $output);
        $this->assertContains('text_line', $output);
        $this->assertContains('time', $output);
        $this->assertContains('url', $output);
        $this->assertContains('audience_targeting_groups', $output);
        $this->assertContains('category_selection', $output);
        $this->assertContains('contact', $output);
        $this->assertContains('smart_content', $output);
        $this->assertContains('teaser_selection', $output);
        $this->assertContains('location', $output);
        $this->assertContains('media_selection', $output);
        $this->assertContains('route', $output);
        $this->assertContains('snippet', $output);
        $this->assertContains('tag_selection', $output);
    }
}
