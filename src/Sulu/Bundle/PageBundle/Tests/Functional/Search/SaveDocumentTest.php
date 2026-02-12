<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Tests\Functional\Search;

use Composer\InstalledVersions;
use Massive\Bundle\SearchBundle\Search\Field;
use Massive\Bundle\SearchBundle\Search\QueryHit;
use Massive\Bundle\SearchBundle\Search\SearchResult;
use Sulu\Bundle\PageBundle\Document\PageDocument;
use Sulu\Bundle\SearchBundle\Search\Document;
use Sulu\Component\Content\Document\WorkflowStage;

class SaveDocumentTest extends BaseTestCase
{
    /**
     * Check that the automatic indexing works.
     */
    public function testSaveDocument(): void
    {
        $this->indexDocument('About Us', '/about-us');

        $searchManager = $this->getSearchManager();
        $res = $searchManager->createSearch('About')->locale('de')->index('page_sulu_io')->execute();
        $this->assertCount(1, $res);
        $hit = $res[0];
        $document = $hit->getDocument();

        $this->assertEquals('About Us', $document->getTitle());
        $this->assertEquals('/about-us', $document->getUrl());
        $this->assertEquals(null, $document->getDescription());
    }

    public function testSaveDocumentWithBlocks(): void
    {
        $document = new PageDocument();
        $document->setTitle('Places');
        $document->setStructureType('blocks');
        $document->setResourceSegment('/places');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->getStructure()->bind([
            'block' => [
                [
                    'type' => 'article',
                    'title' => 'Dornbirn',
                    'article' => 'Dornbirn Austrua',
                    'settings' => [],
                ],
                [
                    'type' => 'article',
                    'title' => 'Basel',
                    'article' => 'Basel Switzerland',
                    'lines' => ['line1', 'line2'],
                ],
            ],
        ], false);
        $document->setParent($this->homeDocument);

        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $searchManager = $this->getSearchManager();

        $searches = [
            'Places' => 1,
            'Basel' => 1,
            'Dornbirn' => 1,
        ];

        foreach ($searches as $search => $count) {
            $res = $searchManager->createSearch($search)->locale('de')->index('page_sulu_io')->execute();
            $this->assertCount($count, $res, 'Searching for: ' . $search);
        }
    }

    public function testSaveDocumentWithScheduledBlock(): void
    {
        $document = new PageDocument();
        $document->setTitle('Places');
        $document->setStructureType('blocks');
        $document->setResourceSegment('/places');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->getStructure()->bind([
            'block' => [
                [
                    'type' => 'article',
                    'title' => 'Dornbirn',
                    'article' => 'Dornbirn Austrua',
                    'settings' => [
                        'schedules_enabled' => false,
                    ],
                ],
                [
                    'type' => 'article',
                    'title' => 'Basel',
                    'article' => 'Basel Switzerland',
                    'lines' => ['line1', 'line2'],
                    'settings' => [
                        'schedules_enabled' => true,
                    ],
                ],
            ],
        ], false);
        $document->setParent($this->homeDocument);

        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $searchManager = $this->getSearchManager();

        $searches = [
            'Places' => 1,
            'Basel' => 0,
            'Dornbirn' => 1,
        ];

        foreach ($searches as $search => $count) {
            $res = $searchManager->createSearch($search)->locale('de')->index('page_sulu_io')->execute();
            $this->assertCount($count, $res, 'Searching for: ' . $search);
        }
    }

    public function testSaveDocumentWithHiddenBlock(): void
    {
        $document = new PageDocument();
        $document->setTitle('Places');
        $document->setStructureType('blocks');
        $document->setResourceSegment('/places');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->getStructure()->bind([
            'block' => [
                [
                    'type' => 'article',
                    'title' => 'Dornbirn',
                    'article' => 'Dornbirn Austria',
                    'settings' => [
                        'hidden' => false,
                    ],
                ],
                [
                    'type' => 'article',
                    'title' => 'Basel',
                    'article' => 'Basel Switzerland',
                    'lines' => ['line1', 'line2'],
                    'settings' => [
                        'hidden' => true,
                    ],
                ],
            ],
        ], false);
        $document->setParent($this->homeDocument);

        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $searchManager = $this->getSearchManager();

        $searches = [
            'Places' => 1,
            'Basel' => 0,
            'Dornbirn' => 1,
        ];

        foreach ($searches as $search => $count) {
            $res = $searchManager->createSearch($search)->locale('de')->index('page_sulu_io')->execute();
            $this->assertCount($count, $res, 'Searching for: ' . $search);
        }
    }

    public function testSaveDocumentStripTags(): void
    {
        $version = InstalledVersions::getPrettyVersion('massive/search-bundle');
        if (\version_compare($version, '2.10.0', '<')) {
            $this->markTestSkipped('This feature requires atleast "massive/search-bundle" of 2.10.0.');
        }

        $document = new PageDocument();
        $document->setTitle('Places');
        $document->setStructureType('text_editor');
        $document->setResourceSegment('/places');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->getStructure()->bind([
            'title' => 'Sulu',
            'article' => '<p>Sulu is the best</p>',
            'block' => [
                [
                    'type' => 'article',
                    'title' => 'Sulu',
                    'article' => '<p><strong>Sulu</strong> is very cool</p>',
                ],
            ],
        ], false);
        $document->setParent($this->homeDocument);

        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $searchManager = $this->getSearchManager();

        /** @var SearchResult $res */
        $res = $searchManager->createSearch('Sulu')->locale('de')->index('page_sulu_io')->execute();

        /** @var QueryHit $hit */
        foreach ($res as $hit) {
            /** @var Document $document */
            $document = $hit->getDocument();
            $this->assertSame('Sulu is the best', $document->getDescription());
            $articleBlockValue = '';
            /** @var Field[] $fields */
            $fields = $document->getFields();

            foreach ($fields as $field) {
                if ('block_article_article' === $field->getName()) {
                    $articleBlockValue = $field->getValue();
                }
            }

            $this->assertSame('Sulu is very cool', $articleBlockValue, 'Article block value should be stripped of tags');
        }
    }
}
