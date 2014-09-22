<?php

namespace Sulu\Bundle\SearchBundle\Tests\Functional;

use Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity\Product;
use Sulu\Bundle\SearchBundle\Tests\Fixtures\DefaultStructureCache;

class SearchManagerTest extends BaseTestCase
{
    public function testSearchManager()
    {
        $nbResults = 10;

        // ensure that we do not create new documents for existing IDs
        for ($i = 1; $i <= 2; $i++) {

            $this->generateStructureIndex($nbResults);
            $res = $this->getSearchManager()->createSearch('Structure*')->locale('de')->index('content')->go();

            $this->assertCount($nbResults, $res);
        }
    }
}
