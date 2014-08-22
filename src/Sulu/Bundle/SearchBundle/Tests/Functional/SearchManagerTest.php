<?php

namespace Sulu\Bundle\SearchBundle\Tests\Functional;

use Symfony\Cmf\Component\Testing\Functional\BaseTestCase;
use Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity\Product;
use Sulu\Bundle\SearchBundle\Tests\Fixtures\DefaultStructureCache;

class SearchManagerTest extends BaseTestCase
{
    public function testSearchManager()
    {
        $nbResults = 10;
        $searchManager = $this->getContainer()->get('massive_search.search_manager');

        // ensure that we do not create new documents for existing IDs
        for ($i = 1; $i <= 2; $i++) {

            for ($i = 1; $i <= $nbResults; $i++) {
                $structure = new DefaultStructureCache();
                $structure->setUuid($i);
                $structure->getProperty('title')->setValue('Structure Title ' . $i);
                $structure->getProperty('title')->setIndexed(true);

                $structure->getProperty('url')->setValue('/');
                $structure->getProperty('url')->setIndexed(false);

                $searchManager->index($structure, 'content');
            }

            $res = $searchManager->search('Structure*', 'content');

            $this->assertCount($nbResults, $res);
        }
    }
}
