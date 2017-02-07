<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Search;

use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Component\Content\Document\WorkflowStage;

class SaveDocumentTest extends BaseTestCase
{
    /**
     * Check that the automatic indexing works.
     */
    public function testSaveDocument()
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

    public function testSaveDocumentWithBlocks()
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
                ],
                [
                    'type' => 'article',
                    'title' => 'Basel',
                    'article' => 'Basel Switzerland',
                    'lines' => ['line1', 'line2'],
                ],
            ], ], false);
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
}
