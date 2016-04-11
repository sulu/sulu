<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Command;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SnippetLocaleCopyCommandTest extends SuluTestCase
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

        $command = new SnippetLocaleCopyCommand();
        $command->setApplication($application);
        $command->setContainer($this->getContainer());
        $this->tester = new CommandTester($command);
    }

    public function testRun()
    {
        $this->initPhpcr();

        $snippet = $this->documentManager->create('snippet');
        $snippet->setStructureType('car');
        $snippet->setTitle('Hallo');
        $snippet->getStructure()->bind(['description' => 'This is a perfect description.']);
        $this->documentManager->persist(
            $snippet,
            'en',
            [
                'parent_path' => '/cmf/snippets/car',
                'auto_create' => true,
            ]
        );
        $this->documentManager->flush();

        $this->tester->execute(
            [
                'srcLocale' => 'en',
                'destLocale' => 'de',
            ]
        );
        $output = $this->tester->getDisplay();
        $this->assertContains('Done', $output);

        $this->documentRegistry->clear();

        $resultEN = $this->documentManager->find($snippet->getUuid(), 'en');
        $resultDE = $this->documentManager->find($snippet->getUuid(), 'de');

        $this->assertEquals('Hallo', $resultDE->getTitle());
        $this->assertEquals('Hallo', $resultEN->getTitle());

        $this->assertEquals('This is a perfect description.', $resultDE->getStructure()->getProperty('description')->getValue());
        $this->assertEquals('This is a perfect description.', $resultEN->getStructure()->getProperty('description')->getValue());
    }
}
