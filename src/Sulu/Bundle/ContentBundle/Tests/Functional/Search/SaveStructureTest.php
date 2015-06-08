<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional\Search;

use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\SearchBundle\Tests\Fixtures\SecondStructureCache;
use Sulu\Component\Content\Compat\PropertyTag;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Mapper\ContentMapperRequest;

class SaveStructureTest extends BaseTestCase
{
    /**
     * Check that the automatic indexing works.
     */
    public function testSaveStructure()
    {
        $this->indexStructure('About Us', '/about-us');

        $searchManager = $this->getSearchManager();
        $res = $searchManager->createSearch('About')->locale('de')->index('page')->execute();
        $this->assertCount(1, $res);
        $hit = $res[0];
        $document = $hit->getDocument();

        $this->assertEquals('About Us', $document->getTitle());
        $this->assertEquals('/about-us', $document->getUrl());
        $this->assertEquals(null, $document->getDescription());
    }

    public function testSaveStructureWithBlocks()
    {
        $document = new PageDocument();
        $document->setTitle('Places');
        $document->setStructureType('blocks');
        $document->setResourceSegment('/places');
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->getStructure()->bind(array(
            'block' => array(
                array(
                    'type' => 'article',
                    'title' => 'Dornbirn',
                    'article' => 'Dornbirn Austrua',
                ),
                array(
                    'type' => 'article',
                    'title' => 'Basel',
                    'article' => 'Basel Switzerland',
                ),
            )), false);
        $document->setParent($this->webspaceDocument);

        $this->documentManager->persist($document, 'de');
        $this->documentManager->flush();

        $searchManager = $this->getSearchManager();

        $searches = array(
            'Places' => 1,
            'Basel' => 1,
            'Dornbirn' => 1,
        );

        foreach ($searches as $search => $count) {
            $res = $searchManager->createSearch($search)->locale('de')->index('page')->execute();
            $this->assertCount($count, $res, 'Searching for: ' . $search);
        }
    }
}
