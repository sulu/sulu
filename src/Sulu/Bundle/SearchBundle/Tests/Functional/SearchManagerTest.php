<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Tests\Functional;

class SearchManagerTest extends BaseTestCase
{
    public function testSearchManager()
    {
        $nbResults = 10;

        // ensure that we do not create new documents for existing IDs
        for ($i = 1; $i <= 2; $i++) {

            $this->generateStructureIndex($nbResults);
            $res = $this->getSearchManager()->createSearch('Structure')->locale('de')->index('content')->execute();

            $this->assertCount($nbResults, $res);
        }
    }

    public function testSearchByWebspace()
    {
        $this->generateStructureIndex(4, 'webspace_four');
        $this->generateStructureIndex(2, 'webspace_two');
        $res = $this->getSearchManager()->createSearch('Structure')->locale('de')->index('content')->execute();
        $this->assertCount(6, $res);

        if (!$this->getContainer()->get('massive_search.adapter') instanceof \Massive\Bundle\SearchBundle\Search\Adapter\ZendLuceneAdapter) {
            $this->markTestSkipped('Skipping zend lucene specific test');
            return;
        }

        // TODO: This should should not be here
        $res = $this->getSearchManager()->createSearch('+webspaceKey:webspace_four Structure*')->locale('de')->index('content')->execute();
        $this->assertCount(4, $res);
    }
}
