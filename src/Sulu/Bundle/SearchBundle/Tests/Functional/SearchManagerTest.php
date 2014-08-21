<?php

namespace Sulu\Bundle\SearchBundle\Tests\Functional;

use Symfony\Cmf\Component\Testing\Functional\BaseTestCase;
use Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity\Product;

class SearchManagerTest extends BaseTestCase
{
    public function testSearchManager()
    {
        $nbResults = 10;
        $searchManager = $this->getContainer()->get('sulu_search.search_manager');

        // ensure that we do not create new documents for existing IDs
        for ($i = 1; $i <= 2; $i++) {

            for ($i = 1; $i <= $nbResults; $i++) {
                $product = new Product();
                $product->setId($i);
                $product->setTitle('Hello this is a product '.$i);
                $product->setBody('To be or not to be, that is the question');

                $searchManager->index($product);
            }

            $res = $searchManager->search('Hello*', 'product');

            $this->assertCount($nbResults, $res);
        }
    }
}
