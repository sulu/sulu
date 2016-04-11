<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Command;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ContentLocaleCopyCommandTest extends SuluTestCase
{
    /**
     * @var CommandTester
     */
    private $tester;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    public function setUp()
    {
        $application = new Application($this->getContainer()->get('kernel'));
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->documentRegistry = $this->getContainer()->get('sulu_document_manager.document_registry');

        $command = new ContentLocaleCopyCommand();
        $command->setApplication($application);
        $command->setContainer($this->getContainer());
        $this->tester = new CommandTester($command);
    }

    public function testRun()
    {
        $this->initPhpcr();

        $page = $this->documentManager->create('page');
        $page->setStructureType('default');
        $page->setTitle('Hallo');
        $page->getStructure()->bind(['article' => 'This is a perfect description.']);
        $page->setResourceSegment('/hallo');
        $this->documentManager->persist(
            $page,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents',
            ]
        );
        $this->documentManager->flush();

        $this->tester->execute(
            [
                'webspaceKey' => 'sulu_io',
                'srcLocale' => 'de',
                'destLocale' => 'en',
            ]
        );
        $output = $this->tester->getDisplay();
        $this->assertContains('Done', $output);

        $this->documentRegistry->clear();

        $resultEN = $this->documentManager->find($page->getUuid(), 'en');
        $resultDE = $this->documentManager->find($page->getUuid(), 'de');

        $this->assertEquals('Hallo', $resultDE->getTitle());
        $this->assertEquals('Hallo', $resultEN->getTitle());

        $this->assertEquals('This is a perfect description.', $resultDE->getStructure()->getProperty('article')->getValue());
        $this->assertEquals('This is a perfect description.', $resultEN->getStructure()->getProperty('article')->getValue());

        $this->assertEquals('/hallo', $resultDE->getResourceSegment());
        $this->assertEquals('/hallo', $resultEN->getResourceSegment());
    }
}
