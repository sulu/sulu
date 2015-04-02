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

use Sulu\Component\Content\Mapper\ContentMapperRequest;

class SearchManagerTest extends BaseTestCase
{
    public function testSearchManager()
    {
        $nbResults = 10;

        // ensure that we do not create new documents for existing IDs
        for ($i = 1; $i <= 2; $i++) {

            $this->generateStructureIndex($nbResults);
            $res = $this->getSearchManager()->createSearch('Structure')->locale('de')->index('page')->execute();

            $this->assertCount($nbResults, $res);
        }
    }

    public function testSearchByWebspace()
    {
        $this->generateStructureIndex(4, 'webspace_four');
        $this->generateStructureIndex(2, 'webspace_two');
        $result = $this->getSearchManager()->createSearch('Structure')->locale('de')->index('page')->execute();
        $this->assertCount(6, $result);

        $firstHit = reset($result);
        $document = $firstHit->getDocument();
        $this->assertEquals('page', $document->getCategory());

        if (!$this->getContainer()->get('massive_search.adapter') instanceof \Massive\Bundle\SearchBundle\Search\Adapter\ZendLuceneAdapter) {
            $this->markTestSkipped('Skipping zend lucene specific test');
            return;
        }

        // TODO: This should should not be here
        $res = $this->getSearchManager()->createSearch('+webspaceKey:webspace_four Structure*')->locale('de')->index('page')->execute();
        $this->assertCount(4, $res);
    }

    public function testHomepage()
    {
        $homepage = $this->contentMapper->loadStartPage('sulu_io', 'en');
        $this->contentMapper->saveRequest(ContentMapperRequest::create()
            ->setWebspaceKey('sulu_io')
            ->setTemplateKey('default')
            ->setUserId(1)
            ->setUuid($homepage->getUuid())
            ->setLocale('en')
            ->setState(2)
            ->setData(array(
                'title' => 'Homepage',
                'url' => '/en'
            ))
        );

        $result = $this->getSearchManager()->createSearch('Homepage')->execute();
        $this->assertCount(1, $result);
        $firstHit = reset($result);
        $document = $firstHit->getDocument();
        $this->markTestSkipped('Cannot determine if the page is the homepage');
        $this->assertEquals('homepage', $document->getCategory());
    }
}
