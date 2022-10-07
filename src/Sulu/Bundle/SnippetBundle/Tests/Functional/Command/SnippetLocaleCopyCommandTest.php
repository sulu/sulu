<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Command;

use Sulu\Bundle\SnippetBundle\Command\SnippetLocaleCopyCommand;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManager;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\HttpKernel\SuluKernel;
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

    public function setUp(): void
    {
        $application = new Application();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->documentRegistry = $this->getContainer()->get('sulu_document_manager_test.document_registry');

        $command = new SnippetLocaleCopyCommand(
            $this->getContainer()->get('sulu_snippet.repository'),
            $this->getContainer()->get('sulu.content.mapper'),
            $this->getContainer()->get('doctrine_phpcr.session'),
            $this->getContainer()->get('sulu_document_manager.document_manager'),
            $this->getContainer()->getParameter('sulu.content.language.namespace')
        );
        $command->setApplication($application);
        $this->tester = new CommandTester($command);
    }

    public function testRun(): void
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
        $this->documentManager->publish($snippet, 'en');
        $this->documentManager->flush();

        $this->tester->execute(
            [
                'srcLocale' => 'en',
                'destLocale' => 'de',
            ]
        );
        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('Done', $output);

        $this->documentRegistry->clear();

        $resultEN = $this->documentManager->find($snippet->getUuid(), 'en');
        $resultDE = $this->documentManager->find($snippet->getUuid(), 'de');

        $this->assertEquals('Hallo', $resultDE->getTitle());
        $this->assertEquals('Hallo', $resultEN->getTitle());

        $this->assertEquals('This is a perfect description.', $resultDE->getStructure()->getProperty('description')->getValue());
        $this->assertEquals('This is a perfect description.', $resultEN->getStructure()->getProperty('description')->getValue());

        $container = self::bootKernel(['sulu.context' => SuluKernel::CONTEXT_WEBSITE])->getContainer();
        $documentManager = $container->get('sulu_document_manager.document_manager');

        $resultEN = $documentManager->find($snippet->getUuid(), 'en');
        $resultDE = $documentManager->find($snippet->getUuid(), 'de');

        $this->assertEquals('Hallo', $resultDE->getTitle());
        $this->assertEquals('Hallo', $resultEN->getTitle());

        $this->assertEquals('This is a perfect description.', $resultDE->getStructure()->getProperty('description')->getValue());
        $this->assertEquals('This is a perfect description.', $resultEN->getStructure()->getProperty('description')->getValue());
    }
}
