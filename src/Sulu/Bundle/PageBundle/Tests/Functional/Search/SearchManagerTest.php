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

use Massive\Bundle\SearchBundle\Search\Document;
use Massive\Bundle\SearchBundle\Search\QueryHit;

class SearchManagerTest extends BaseTestCase
{
    /**
     * The search manager should update existing documents with the same IDs rather
     * than creating new documents.
     */
    public function testSearchManager(): void
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

    public function testSearchByWebspace(): void
    {
        $this->generateDocumentIndex(4, '/test-');
        $this->generateDocumentIndex(2, '/test-1');
        $result = $this->getSearchManager()->createSearch('Document')->locale('de')->index('page_sulu_io')->execute();
        $this->assertCount(6, $result);

        $result->rewind();
        /** @var QueryHit $firstHit */
        $firstHit = $result->current();
        $this->assertInstanceOf(QueryHit::class, $firstHit);
        /** @var Document $document */
        $document = $firstHit->getDocument();
        $this->assertInstanceOf(Document::class, $document);
        $this->assertEquals('page_sulu_io', $document->getIndex());
    }
}
