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
            $res = $this->getSearchManager()->createSearch('Structure*')->locale('de')->index('content')->execute();

            $this->assertCount($nbResults, $res);
        }
    }
}
