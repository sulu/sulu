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

class DeleteDocumentTest extends BaseTestCase
{
    public function testDeleteDocument()
    {
        $searchManager = $this->getSearchManager();
        $mapper = $this->getContainer()->get('sulu.content.mapper');

        $structure = $this->indexDocument('About Us', '/about-us');
        $this->documentManager->flush();

        $result = $searchManager->createSearch('About')->locale('de')->index('page_sulu_io')->execute();
        $this->assertCount(1, $result);

        $mapper->delete($structure->getUuid(), 'sulu_io');
        $result = $searchManager->createSearch('About')->locale('de')->index('page_sulu_io')->execute();

        $this->assertCount(0, $result);
    }
}
