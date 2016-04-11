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

class SearchManagerTest extends BaseTestCase
{
    /**
     * The search manager should update existing documents with the same IDs rather
     * than creating new documents.
     */
    public function testSearchManager()
    {
        $nbResults = 10;
        $documents = $this->generateDocumentIndex($nbResults);

        for ($i = 1; $i <= 2; ++$i) {
            foreach ($documents as $document) {
                $this->documentManager->persist($document, 'de');
            }

            $res = $this->getSearchManager()->createSearch('Document')->locale('de')->index('page_sulu_io')->execute();

            $this->assertCount($nbResults, $res);
        }
    }

    public function testSearchByWebspace()
    {
        $this->generateDocumentIndex(4, '/test-');
        $this->generateDocumentIndex(2, '/test-1');
        $result = $this->getSearchManager()->createSearch('Document')->locale('de')->index('page_sulu_io')->execute();
        $this->assertCount(6, $result);

        $firstHit = reset($result);
        $document = $firstHit->getDocument();
        $this->assertEquals('page_sulu_io', $document->getIndex());

        if (!$this->getContainer()->get('massive_search.adapter') instanceof \Massive\Bundle\SearchBundle\Search\Adapter\ZendLuceneAdapter) {
            $this->markTestSkipped('Skipping zend lucene specific test');

            return;
        }
    }
}
