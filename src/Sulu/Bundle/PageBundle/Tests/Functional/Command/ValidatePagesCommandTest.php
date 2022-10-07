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

use Sulu\Bundle\PageBundle\Command\ValidatePagesCommand;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManager;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ValidatePagesCommandTest extends SuluTestCase
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

        $command = new ValidatePagesCommand(
            $this->getContainer()->get('sulu_document_manager.default_session'),
            $this->getContainer()->get('sulu_core.webspace.webspace_manager'),
            $this->getContainer()->get('sulu.content.structure_manager'),
            $this->getContainer()->get('sulu.content.webspace_structure_provider')
        );
        $command->setApplication($application);
        $this->tester = new CommandTester($command);

        $this->setupPages();
    }

    public function testExecute(): void
    {
        $homeDocument = $this->documentManager->find('/cmf/sulu_io/contents');
        $this->assertCount(4, $homeDocument->getChildren());

        $this->tester->execute([
            'webspaceKey' => 'sulu_io',
        ]);

        // should complain on smartcontent, setupPages() sets up 2 smartcontent nodes
        $output = $this->tester->getDisplay();
        $this->assertStringContainsString('2 Errors found', $output);
    }

    /**
     * Creates pages.
     */
    protected function setupPages()
    {
        $this->initPhpcr();

        $testIoHomeDocument = $this->documentManager->find('/cmf/test_io/contents', 'de');

        $page1 = $this->documentManager->create('page');
        $page1->setStructureType('default');
        $page1->setTitle('Node1');
        $page1->setResourceSegment('/node1');
        $this->documentManager->persist(
            $page1,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents',
            ]
        );
        $this->documentManager->flush();

        $page1_1 = $this->documentManager->create('page');
        $page1_1->setStructureType('default');
        $page1_1->setTitle('Node1-1');
        $page1_1->getStructure()->bind(['article' => 'This is a perfect description.']);
        $page1_1->setResourceSegment('/node1-1');
        $this->documentManager->persist(
            $page1_1,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents/node1',
            ]
        );
        $this->documentManager->flush();

        $page1_1_1 = $this->documentManager->create('page');
        $page1_1_1->setStructureType('default');
        $page1_1_1->setTitle('Node1-1-1');
        $page1_1_1->getStructure()->bind(['article' => 'This is a perfect description.']);
        $page1_1_1->setResourceSegment('/node1-1-1');
        $this->documentManager->persist(
            $page1_1_1,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents/node1/node1-1',
            ]
        );
        $this->documentManager->flush();

        $page2 = $this->documentManager->create('page');
        $page2->setStructureType('smartcontent');
        $page2->setTitle('Node2');
        $page2->getStructure()->bind(
            [
                'title' => 'Node2',
                'smart_content' => [
                    'dataSource' => $page1->getUuid(),
                ],
            ]
        );
        $page2->setResourceSegment('/node2');
        $this->documentManager->persist(
            $page2,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents',
            ]
        );
        $this->documentManager->flush();

        $page2_1 = $this->documentManager->create('page');
        $page2_1->setStructureType('internallinks');
        $page2_1->setTitle('Node2-1');
        $page2_1->getStructure()->bind(
            [
                'title' => 'Node2-1',
                'internalLinks' => [
                    $page1->getUuid(),
                    $page2->getUuid(),
                    $testIoHomeDocument->getUuid(),
                ],
            ]
        );
        $page2_1->setResourceSegment('/node2-1');
        $this->documentManager->persist(
            $page2_1,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents/node2',
            ]
        );
        $this->documentManager->flush();

        $page2_1_1 = $this->documentManager->create('page');
        $page2_1_1->setStructureType('default');
        $page2_1_1->setTitle('Node2-1-1');
        $page2_1_1->getStructure()->bind(['article' => 'This is a perfect description.']);
        $page2_1_1->setResourceSegment('/node2-1-1');
        $this->documentManager->persist(
            $page2_1_1,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents/node2/node2-1',
            ]
        );
        $this->documentManager->flush();

        $page3 = $this->documentManager->create('page');
        $page3->setStructureType('smartcontent');
        $page3->setTitle('Node3');
        $page3->getStructure()->bind(
            [
                'title' => 'Node3',
                'smart_content' => [
                    'dataSource' => $testIoHomeDocument->getUuid(),
                ],
            ]
        );
        $page3->setResourceSegment('/node3');
        $this->documentManager->persist(
            $page3,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents',
            ]
        );
        $this->documentManager->flush();

        $page4 = $this->documentManager->create('page');
        $page4->setStructureType('block');
        $page4->setTitle('Node4');
        $page4->getStructure()->bind(
            [
                'title' => 'Node4',
                'article' => [
                    [
                        'text' => '<p><sulu-link href="' . $page1->getUuid() . '" provider="page" target="_self" title="Link-Title">Link-Title</sulu-link></p>',
                        'title' => 'Node4 block',
                        'type' => 'textEditor',
                    ],
                ],
            ]
        );
        $page4->setResourceSegment('/node4');
        $this->documentManager->persist(
            $page4,
            'de',
            [
                'parent_path' => '/cmf/sulu_io/contents',
            ]
        );
        $this->documentManager->flush();
    }
}
